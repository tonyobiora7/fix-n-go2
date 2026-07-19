<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contract_id');
            $table->string('reference', 100)->unique();
            $table->decimal('amount', 10, 2);
            $table->decimal('platform_fee', 10, 2);
            $table->decimal('net_amount', 10, 2);
            $table->enum('status', ['pending', 'funded', 'held', 'frozen', 'released', 'refunded', 'split', 'cancelled'])->default('pending');
            $table->uuid('payer_id');
            $table->uuid('payee_id');
            $table->dateTime('payment_date')->nullable();
            $table->dateTime('release_date')->nullable();
            $table->dateTime('guarantee_start')->nullable();
            $table->dateTime('guarantee_end')->nullable();
            $table->timestamps();

            $table->index('contract_id');
            $table->index('reference');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};