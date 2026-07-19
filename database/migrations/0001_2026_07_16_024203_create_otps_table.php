<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->id(); // BIGINT auto-increment
            $table->uuid('user_id');
            $table->string('code', 10);
            $table->enum('type', ['registration', 'password_reset']);
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('code');
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('otps');
    }
};