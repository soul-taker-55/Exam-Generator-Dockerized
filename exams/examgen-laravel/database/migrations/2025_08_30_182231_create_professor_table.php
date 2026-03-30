<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('professor', function (Blueprint $table) {
            $table->increments('prof_ID');
            $table->string('username', 255)->unique();
            $table->string('password', 255);
            $table->string('first_name', 255);
            $table->string('last_name', 255)->default('');
            $table->string('email', 255)->unique();
            $table->date('registration_date');
            $table->string('phone_number', 10);
            $table->string('verification_token', 64)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->integer('tokens')->default(10);
        });
    }
    public function down(): void {
        Schema::dropIfExists('professor');
    }
};
