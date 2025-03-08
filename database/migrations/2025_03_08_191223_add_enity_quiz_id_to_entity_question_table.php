<?php

use App\Models\EntityQuestion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("entity_question", function (Blueprint $table) {
            $table
                ->foreignId("entity_quiz_id")
                ->after("id")
                ->nullable()
                ->constrained("entity_quizzes")
                ->onDelete("cascade");
        });

        // Map entity_id to entity_quiz_id
        $entityQuizzes = DB::table("entity_quizzes")->get();
        foreach ($entityQuizzes as $entityQuiz) {
            EntityQuestion::query()
                ->where("entity_id", $entityQuiz->entity_id)
                ->whereHas(
                    "question",
                    fn($q) => $q->where("quiz_id", $entityQuiz->quiz_id),
                )
                ->update(["entity_quiz_id" => $entityQuiz->id]);
        }

        Schema::table("entity_question", function (Blueprint $table) {
            $table->foreignId("entity_quiz_id")->change();

            $table->dropForeign(["entity_id"]);
            $table->dropColumn("entity_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("entity_question", function (Blueprint $table) {
            $table
                ->foreignId("entity_id")
                ->after("id")
                ->nullable()
                ->constrained("entities")
                ->onDelete("cascade");
        });

        // Map entity_quiz_id to entity_id
        $entityQuizzes = DB::table("entity_quizzes")->get();
        foreach ($entityQuizzes as $entityQuiz) {
            EntityQuestion::query()
                ->where("entity_quiz_id", $entityQuiz->id)
                ->update(["entity_id" => $entityQuiz->entity_id]);
        }

        Schema::table("entity_question", function (Blueprint $table) {
            $table->foreignId("entity_id")->change();

            $table->dropForeign(["entity_quiz_id"]);
            $table->dropColumn("entity_quiz_id");
        });
    }
};
