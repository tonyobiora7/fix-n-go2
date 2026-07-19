<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 100);
            $table->uuid('creator_id');
            $table->uuid('recipient_id');
            $table->enum('status', ['active', 'archived', 'closed'])->default('active');
            $table->boolean('contract_created')->default(false);
            $table->uuid('contract_id')->nullable();
            $table->timestamps();

            $table->index('creator_id');
            $table->index('recipient_id');
            $table->index('contract_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chats');
    }
};