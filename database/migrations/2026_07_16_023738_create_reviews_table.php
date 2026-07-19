<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contract_id');
            $table->uuid('reviewer_id');
            $table->uuid('reviewee_id');
            $table->enum('type', ['public', 'private']);
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->enum('status', ['active', 'hidden', 'removed'])->default('active');
            $table->timestamps();

            $table->index('contract_id');
            $table->index('reviewer_id');
            $table->index('reviewee_id');
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
};