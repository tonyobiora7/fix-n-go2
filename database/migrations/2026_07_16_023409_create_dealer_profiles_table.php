<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dealer_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('business_name', 100);
            $table->string('business_logo')->nullable();
            $table->text('business_description')->nullable();
            $table->text('business_address');
            $table->point('business_location');
            $table->json('working_hours')->nullable();
            $table->string('bank_account_name', 100);
            $table->string('bank_account_number', 20);
            $table->string('bank_name', 100);
            $table->string('bank_code', 10);
            $table->string('bvn', 20);
            $table->timestamp('verification_date')->nullable();
            $table->json('gallery_images')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('business_name');
            $table->spatialIndex('business_location');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dealer_profiles');
    }
};