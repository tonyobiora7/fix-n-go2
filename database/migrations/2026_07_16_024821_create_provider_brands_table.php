<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('provider_brands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('provider_id');
            $table->uuid('brand_id');
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('provider_profiles')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('vehicle_brands')->onDelete('cascade');
            $table->unique(['provider_id', 'brand_id']);
            $table->index('provider_id');
            $table->index('brand_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('provider_brands');
    }
};