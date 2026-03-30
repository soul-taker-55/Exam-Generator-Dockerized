<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('token_transactions', function (Blueprint $table) {
            $table->increments('transaction_id');
            $table->unsignedInteger('professor_id');
            $table->integer('tokens_purchased');
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_method', 50)->nullable();
            $table->timestamp('transaction_date')->useCurrent();
            $table->enum('status', ['pending','completed','failed'])->default('pending');
            $table->string('payment_reference', 255)->nullable();

            $table->foreign('professor_id')->references('prof_ID')->on('professor')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('token_transactions');
    }
};
