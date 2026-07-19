<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->enum('type', ['registration', 'verification', 'subscription', 'chat', 'contract', 'payment', 'guarantee', 'dispute', 'review']);
            $table->enum('channel', ['push', 'email', 'in_app']);
            $table->string('title', 100);
            $table->text('body');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('is_read');
            $table->index('sent_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};