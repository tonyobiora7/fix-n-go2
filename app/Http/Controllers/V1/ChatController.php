<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Shared\Enums\MessageType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Create a new chat.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|exists:users,id',
            'title' => 'required|string|min:3|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $recipientId = $request->recipient_id;

        // Prevent self-chat
        if ($user->id === $recipientId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot create a chat with yourself.',
            ], 400);
        }

        $recipient = User::find($recipientId);

        // Check if recipient exists and is active
        if (!$recipient || $recipient->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Recipient not found or not active.',
            ], 404);
        }

        // Check if a chat already exists between these two users
        $existingChat = Chat::where(function ($query) use ($user, $recipientId) {
            $query->where('creator_id', $user->id)
                ->where('recipient_id', $recipientId);
        })->orWhere(function ($query) use ($user, $recipientId) {
            $query->where('creator_id', $recipientId)
                ->where('recipient_id', $user->id);
        })->where('status', 'active')->first();

        if ($existingChat) {
            return response()->json([
                'success' => false,
                'message' => 'A chat already exists between you and this user.',
                'data' => [
                    'chat_id' => $existingChat->id,
                    'chat' => $this->formatChat($existingChat, $user->id),
                ],
            ], 409);
        }

        // Check if the user is allowed to chat with the recipient
        $canChat = $this->canUserChatWith($user, $recipient);

        if (!$canChat) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to chat with this user.',
            ], 403);
        }

        $chat = Chat::create([
            'title' => $request->title,
            'creator_id' => $user->id,
            'recipient_id' => $recipientId,
            'status' => 'active',
            'contract_created' => false,
        ]);

        // Create system message
        $chat->createSystemMessage('Chat created: ' . $request->title);

        return response()->json([
            'success' => true,
            'message' => 'Chat created successfully.',
            'data' => [
                'chat_id' => $chat->id,
                'chat' => $this->formatChat($chat, $user->id),
            ],
        ], 201);
    }

    /**
     * Get all chats for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);

        $chats = Chat::where('creator_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->where('status', 'active')
            ->orderBy('updated_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        $formattedChats = $chats->map(function ($chat) use ($user) {
            return $this->formatChat($chat, $user->id);
        });

        return response()->json([
            'success' => true,
            'data' => $formattedChats,
            'meta' => [
                'total' => $chats->total(),
                'current_page' => $chats->currentPage(),
                'per_page' => $chats->perPage(),
                'last_page' => $chats->lastPage(),
            ],
        ]);
    }

    /**
     * Get messages for a specific chat.
     *
     * @param Request $request
     * @param string $chatId
     * @return \Illuminate\Http\JsonResponse
     */
    public function messages(Request $request, string $chatId)
    {
        $user = $request->user();
        $chat = Chat::with(['creator', 'recipient'])->find($chatId);

        if (!$chat) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found.',
            ], 404);
        }

        // Check if user is a participant
        if (!$chat->isParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this chat.',
            ], 403);
        }

        $limit = $request->input('limit', 50);
        $page = $request->input('page', 1);

        $messages = $chat->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->paginate($limit, ['*'], 'page', $page);

        // Mark messages as read
        $chat->markAsReadForUser($user->id);

        $formattedMessages = $messages->map(function ($message) {
            return $this->formatMessage($message);
        });

        return response()->json([
            'success' => true,
            'data' => $formattedMessages,
            'meta' => [
                'total' => $messages->total(),
                'current_page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'last_page' => $messages->lastPage(),
            ],
        ]);
    }

    /**
     * Send a message in a chat.
     *
     * @param Request $request
     * @param string $chatId
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request, string $chatId)
    {
        $user = $request->user();
        $chat = Chat::find($chatId);

        if (!$chat) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found.',
            ], 404);
        }

        // Check if user is a participant
        if (!$chat->isParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this chat.',
            ], 403);
        }

        // Check if chat is active
        if ($chat->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This chat is not active.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:text,image',
            'content' => 'required_if:type,text|string|max:5000',
            'image' => 'required_if:type,image|image|mimes:jpeg,png,webp|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $messageData = [
            'chat_id' => $chat->id,
            'sender_id' => $user->id,
            'type' => $request->type,
            'content' => $request->content ?? '',
            'is_read' => false,
        ];

        // Handle image upload
        if ($request->type === 'image' && $request->hasFile('image')) {
            $image = $request->file('image');
            $path = $this->uploadImage($image, 'chat_images');
            
            $messageData['image_url'] = $path;
            $messageData['image_size'] = $image->getSize();
            $messageData['content'] = '📷 Image';
        }

        $message = $chat->messages()->create($messageData);

        // Update chat timestamp
        $chat->touch();

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => $this->formatMessage($message),
        ], 201);
    }

    /**
     * Mark messages as read in a chat.
     *
     * @param Request $request
     * @param string $chatId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, string $chatId)
    {
        $user = $request->user();
        $chat = Chat::find($chatId);

        if (!$chat) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found.',
            ], 404);
        }

        // Check if user is a participant
        if (!$chat->isParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this chat.',
            ], 403);
        }

        $count = $chat->markAsReadForUser($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read.',
            'data' => [
                'marked_count' => $count,
            ],
        ]);
    }

    /**
     * Get chat details.
     *
     * @param Request $request
     * @param string $chatId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $chatId)
    {
        $user = $request->user();
        $chat = Chat::with(['creator', 'recipient', 'messages' => function ($query) {
            $query->latest()->limit(1);
        }])->find($chatId);

        if (!$chat) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found.',
            ], 404);
        }

        // Check if user is a participant
        if (!$chat->isParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a participant in this chat.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatChat($chat, $user->id),
        ]);
    }

    /**
     * Check if a user can chat with another user.
     *
     * @param User $user
     * @param User $recipient
     * @return bool
     */
    private function canUserChatWith(User $user, User $recipient): bool
    {
        // Client can chat with Provider and Dealer
        if ($user->isClient()) {
            return $recipient->isProvider() || $recipient->isDealer();
        }

        // Provider can chat with Client and Dealer
        if ($user->isProvider()) {
            return $recipient->isClient() || $recipient->isDealer();
        }

        // Dealer can chat with Client and Provider
        if ($user->isDealer()) {
            return $recipient->isClient() || $recipient->isProvider();
        }

        return false;
    }

    /**
     * Format chat data for response.
     *
     * @param Chat $chat
     * @param string $userId
     * @return array
     */
    private function formatChat(Chat $chat, string $userId): array
    {
        $otherParticipant = $chat->getOtherParticipant($userId);
        $lastMessage = $chat->getLastMessageAttribute();
        $unreadCount = $chat->getUnreadCountForUser($userId);

        return [
            'id' => $chat->id,
            'title' => $chat->title,
            'status' => $chat->status,
            'contract_created' => (bool) $chat->contract_created,
            'contract_id' => $chat->contract_id,
            'other_participant' => $otherParticipant ? [
                'id' => $otherParticipant->id,
                'full_name' => $otherParticipant->full_name,
                'role' => $otherParticipant->role,
                'profile_image' => $this->getProfileImage($otherParticipant),
            ] : null,
            'last_message' => $lastMessage ? $this->formatMessage($lastMessage) : null,
            'unread_count' => $unreadCount,
            'created_at' => $chat->created_at->toISOString(),
            'updated_at' => $chat->updated_at->toISOString(),
        ];
    }

    /**
     * Format message data for response.
     *
     * @param Message $message
     * @return array
     */
    private function formatMessage(Message $message): array
    {
        $sender = $message->sender;

        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_name' => $sender ? $sender->full_name : 'System',
            'type' => $message->type,
            'content' => $message->getDisplayContent(),
            'image_url' => $message->image_url,
            'image_size' => $message->image_size,
            'is_system' => $message->isSystem(),
            'is_read' => (bool) $message->is_read,
            'read_at' => $message->read_at ? $message->read_at->toISOString() : null,
            'created_at' => $message->created_at->toISOString(),
        ];
    }

    /**
     * Get profile image for a user.
     *
     * @param User $user
     * @return string|null
     */
    private function getProfileImage(User $user): ?string
    {
        if ($user->isClient() && $user->clientProfile) {
            return $user->clientProfile->profile_image;
        }

        if ($user->isProvider() && $user->providerProfile) {
            return $user->providerProfile->business_logo;
        }

        if ($user->isDealer() && $user->dealerProfile) {
            return $user->dealerProfile->business_logo;
        }

        return null;
    }

    /**
     * Upload an image to storage.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @param string $folder
     * @return string
     */
    private function uploadImage($image, string $folder): string
    {
        $extension = $image->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = $folder . '/' . $filename;

        // Use the filesystem disk configured for uploads
        $disk = config('filesystems.default', 'local');
        
        // If using local disk, store in public directory
        if ($disk === 'local') {
            $image->storeAs('public/' . $folder, $filename);
            return asset('storage/' . $folder . '/' . $filename);
        }

        // For R2 or other cloud storage
        $image->storeAs($folder, $filename, $disk);
        
        // Get the URL
        if ($disk === 'r2') {
            $url = config('filesystems.disks.r2.endpoint') . '/' . config('filesystems.disks.r2.bucket') . '/' . $path;
            return $url;
        }

        return $image->storeAs($folder, $filename, $disk);
    }
}