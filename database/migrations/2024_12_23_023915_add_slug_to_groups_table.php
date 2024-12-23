<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("groups", function (Blueprint $table) {
            $table->string("slug")->nullable()->after("name");

            $table->unique(["game_id", "slug"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("groups", function (Blueprint $table) {
            $table->dropUnique(["game_id", "slug"]);

            $table->dropColumn("slug");
        });
    }
};
