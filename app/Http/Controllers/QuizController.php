<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuizResource;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $quizzes = Quiz::query();

        if ($request->has("entity")) {
            $quizzes
                ->leftJoin("entity_quiz", "entity_id", "=", $request->entity)
                ->selectRaw("quizzes.*, entity_id");
        }

        $quizzes = $quizzes->get();

        return response()->json([
            "quizzes" => QuizResource::collection($quizzes),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($group, $slug)
    {
        $quiz = Quiz::query()
            ->whereRelation("group", "slug", "=", $group)
            ->where("slug", $slug)
            ->with("questions.options")
            ->firstOrFail();

        return response()->json([
            "quiz" => QuizResource::make($quiz),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return response()->json([
            "message" => "Quiz deleted successfully",
            "quiz" => QuizResource::make($quiz),
        ]);
    }
}
