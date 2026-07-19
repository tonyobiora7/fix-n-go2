<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contract_id');
            $table->uuid('payer_id');
            $table->uuid('payee_id');
            $table->text('reason');
            $table->text('description');
            $table->enum('status', ['open', 'awaiting_response', 'under_review', 'resolved', 'closed'])->default('open');
            $table->json('evidence')->nullable();
            $table->uuid('admin_id')->nullable();
            $table->enum('admin_decision', ['release', 'refund', 'split'])->nullable();
            $table->text('resolution_notes')->nullable();
            $table->dateTime('opened_at')->useCurrent();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index('contract_id');
            $table->index('payer_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('disputes');
    }
};