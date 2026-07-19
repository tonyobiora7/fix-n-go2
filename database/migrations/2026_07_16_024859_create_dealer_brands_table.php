<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dealer_brands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dealer_id');
            $table->uuid('brand_id');
            $table->timestamps();

            $table->foreign('dealer_id')->references('id')->on('dealer_profiles')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('vehicle_brands')->onDelete('cascade');
            $table->unique(['dealer_id', 'brand_id']);
            $table->index('dealer_id');
            $table->index('brand_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dealer_brands');
    }
};