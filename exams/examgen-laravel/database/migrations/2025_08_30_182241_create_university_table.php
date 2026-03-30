<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('university', function (Blueprint $table) {
            $table->increments('university_ID');
            $table->string('university_name_en', 255);
            $table->string('university_name_ar', 255);
        });
    }
    public function down(): void {
        Schema::dropIfExists('university');
    }
};
