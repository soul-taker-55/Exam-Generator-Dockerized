<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('exam_question', function (Blueprint $table) {
            $table->increments('exam_question_id');
            $table->unsignedInteger('exam_id');
            $table->unsignedInteger('question_id');
            $table->integer('question_order');
            $table->integer('marks');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['exam_id','question_id'], 'uq_exam_question');

            $table->foreign('exam_id')->references('exam_ID')->on('exam')->onDelete('cascade');
            $table->foreign('question_id')->references('question_ID')->on('question')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('exam_question');
    }
};
