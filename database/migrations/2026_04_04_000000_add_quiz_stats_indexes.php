<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("entity_quizzes", function (Blueprint $table) {
            $table->index(["quiz_id", "entity_id"], "entity_quizzes_quiz_entity_idx");
            $table->index(["entity_id", "quiz_id"], "entity_quizzes_entity_quiz_idx");
        });

        Schema::table("entity_question", function (Blueprint $table) {
            $table->index(["entity_quiz_id", "question_id"], "entity_question_entity_quiz_question_idx");
        });

        Schema::table("questions", function (Blueprint $table) {
            $table->index(["quiz_id", "order"], "questions_quiz_order_idx");
        });

        Schema::table("quizzes", function (Blueprint $table) {
            $table->index(["group_id", "published_at"], "quizzes_group_published_idx");
        });
    }

    public function down(): void
    {
        Schema::table("entity_quizzes", function (Blueprint $table) {
            $table->dropIndex("entity_quizzes_quiz_entity_idx");
            $table->dropIndex("entity_quizzes_entity_quiz_idx");
        });

        Schema::table("entity_question", function (Blueprint $table) {
            $table->dropIndex("entity_question_entity_quiz_question_idx");
        });

        Schema::table("questions", function (Blueprint $table) {
            $table->dropIndex("questions_quiz_order_idx");
        });

        Schema::table("quizzes", function (Blueprint $table) {
            $table->dropIndex("quizzes_group_published_idx");
        });
    }
};
