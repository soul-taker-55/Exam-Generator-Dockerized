<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('question', function (Blueprint $table) {
            $table->increments('question_ID');
            $table->unsignedInteger('professor_ID');
            $table->unsignedInteger('subject_ID');
            $table->text('question_text');
            $table->string('img_path', 1024)->nullable();
            $table->integer('difficulty');

            $table->text('ans_A'); $table->boolean('is_correct_A');
            $table->text('ans_B'); $table->boolean('is_correct_B');
            $table->text('ans_C'); $table->boolean('is_correct_C');
            $table->text('ans_D'); $table->boolean('is_correct_D');
            $table->text('ans_E'); $table->boolean('is_correct_E');

            $table->integer('group_num');
            $table->boolean('is_sub');
            $table->date('date');
            $table->integer('mark');

            $table->foreign('professor_ID')->references('prof_ID')->on('professor')->onDelete('cascade');
            $table->foreign('subject_ID')->references('subject_ID')->on('subject')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('question');
    }
};
