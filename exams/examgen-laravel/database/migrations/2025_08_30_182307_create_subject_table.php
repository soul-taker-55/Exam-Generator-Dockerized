<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('subject', function (Blueprint $table) {
            $table->increments('subject_ID');
            $table->unsignedInteger('university_ID');
            $table->unsignedInteger('professor_ID');
            $table->string('subject_name', 255);
            $table->integer('total_mark');
            $table->string('duration', 25);

            $table->foreign('professor_ID')->references('prof_ID')->on('professor')->onDelete('cascade');
            $table->foreign('university_ID')->references('university_ID')->on('university')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('subject');
    }
};
