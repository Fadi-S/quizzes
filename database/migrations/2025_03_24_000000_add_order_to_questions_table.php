<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedSmallInteger('order')->default(0)->after('quiz_id');
        });

        // Set order based on existing id order within each quiz
        DB::statement('
            UPDATE questions q
            JOIN (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY quiz_id ORDER BY id) as row_num
                FROM questions
            ) ranked ON q.id = ranked.id
            SET q.`order` = ranked.row_num
        ');
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
