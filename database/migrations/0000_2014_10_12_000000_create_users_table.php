<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name', 100);
            $table->string('phone', 20)->unique();
            $table->string('email', 100)->unique();
            $table->string('password_hash');
            $table->enum('role', ['client', 'provider', 'dealer', 'admin']);
            $table->enum('status', ['active', 'suspended', 'closed', 'pending_verification', 'awaiting_bvn', 'verification_failed'])->default('active');
            $table->boolean('phone_verified')->default(false);
            $table->boolean('bvn_verified')->default(false);
            $table->timestamp('bvn_verification_date')->nullable();
            $table->enum('bvn_verification_status', ['pending', 'verified', 'failed'])->nullable();
            $table->boolean('profile_complete')->default(false);
            $table->dateTime('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone');
            $table->index('email');
            $table->index('role');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};