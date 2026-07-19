<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->enum('type', ['trial', 'paid']);
            $table->enum('status', ['trial_active', 'paid_active', 'expired', 'grace_period', 'suspended', 'inactive'])->default('inactive');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->dateTime('grace_end_date')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('end_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
};