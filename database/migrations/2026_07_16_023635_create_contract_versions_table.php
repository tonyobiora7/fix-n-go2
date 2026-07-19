<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contract_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contract_id');
            $table->integer('version_number');
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->integer('guarantee_days')->default(0);
            $table->enum('payment_method', ['direct', 'protected']);
            $table->uuid('modified_by');
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected']);
            $table->timestamps();

            $table->index('contract_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contract_versions');
    }
};