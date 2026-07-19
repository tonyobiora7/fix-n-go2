<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chat_id');
            $table->enum('type', ['job', 'supply']);
            $table->enum('status', ['draft', 'pending_acceptance', 'active', 'completed', 'guaranteed', 'disputed', 'cancelled', 'closed'])->default('draft');
            $table->uuid('client_id');
            $table->uuid('provider_id')->nullable();
            $table->uuid('dealer_id')->nullable();
            $table->json('vehicle_snapshot')->nullable();
            $table->text('description');
            $table->integer('quantity')->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['direct', 'protected']);
            $table->decimal('platform_fee', 10, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->nullable();
            $table->integer('guarantee_days')->default(0);
            $table->integer('current_version')->default(1);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('chat_id');
            $table->index('client_id');
            $table->index('provider_id');
            $table->index('dealer_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
};