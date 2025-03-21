<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuizResource;
use App\Models\EntityQuestion;
use App\Models\EntityQuiz;
use Illuminate\Http\Request;

class ResponsesController extends Controller
{
    public function markAsCorrect(EntityQuestion $response)
    {
        if ($response->is_correct) {
            return response()->json(
                [
                    "message" => "Response already marked as correct",
                ],
                400,
            );
        }

        $response->load("question");

        $entityQuiz = EntityQuiz::findOrFail($response->entity_quiz_id);

        \DB::beginTransaction();

        $response->is_correct = true;
        $response->points = $response->question->points;

        $entityQuiz->points += $response->question->points;

        $response->save();
        $entityQuiz->save();

        \DB::commit();

        return response()->json([
            "message" => "Response marked as correct",
            "points" => $response->points,
            "entity_id" => $entityQuiz->entity_id,
            "quiz_id" => $response->question->quiz_id,
            "question_id" => $response->question_id,
        ]);
    }

    public function delete(EntityQuiz $response)
    {
        $response->load("quiz");
        $response->delete();

        return response()->json([
            "message" => "Response deleted",
            "points" => $response->points,
            "entity_id" => $response->entity_id,
            "quiz" => QuizResource::make($response->quiz),
            "response_at" => $response->created_at?->format("Y-m-d H:i:s"),
        ]);
    }
}
