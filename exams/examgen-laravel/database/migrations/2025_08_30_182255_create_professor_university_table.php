<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('professor_university', function (Blueprint $table) {
            $table->increments('prof_uni_ID');
            $table->unsignedInteger('professor_ID');
            $table->unsignedInteger('university_ID');

            $table->foreign('professor_ID')->references('prof_ID')->on('professor')->onDelete('cascade');
            $table->foreign('university_ID')->references('university_ID')->on('university')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('professor_university');
    }
};
