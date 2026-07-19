<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('provider_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('provider_id');
            $table->uuid('category_id');
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('provider_profiles')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('service_categories')->onDelete('cascade');
            $table->unique(['provider_id', 'category_id']);
            $table->index('provider_id');
            $table->index('category_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('provider_categories');
    }
};