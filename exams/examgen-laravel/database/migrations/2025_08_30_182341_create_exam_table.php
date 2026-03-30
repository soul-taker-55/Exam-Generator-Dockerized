<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('exam', function (Blueprint $table) {
            $table->increments('exam_ID');
            $table->unsignedInteger('professor_ID');
            $table->unsignedInteger('university_ID');
            $table->unsignedInteger('subject_ID');
            $table->date('creation_date');
            $table->date('exam_date')->nullable();
            $table->string('pdf_file_path', 255);

            $table->foreign('professor_ID')->references('prof_ID')->on('professor')->onDelete('cascade');
            $table->foreign('university_ID')->references('university_ID')->on('university');
            $table->foreign('subject_ID')->references('subject_ID')->on('subject');
        });
    }
    public function down(): void {
        Schema::dropIfExists('exam');
    }
};
