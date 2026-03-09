<?php

namespace App\Http\Controllers;

use App\Enums\QuestionType;
use App\Http\Resources\QuizResource;
use App\Models\EntityQuestion;
use App\Models\EntityQuiz;
use Illuminate\Http\Request;

class ResponsesController extends Controller
{
    public function update(Request $request, EntityQuestion $response)
    {
        $response->load("question");

        $validated = $request->validate([
            "answer" => ["required"],
        ]);

        $answer = $validated["answer"];
        $question = $response->question;

        if ($question->type === QuestionType::Written && !is_string($answer)) {
            return response()->json([
                "message" => "Answer must be a string",
            ], 422);
        }

        if ($question->type === QuestionType::Choose && !is_string($answer) && !is_int($answer)) {
            return response()->json([
                "message" => "Answer must be a single value",
            ], 422);
        }

        if ($question->type === QuestionType::Order && !is_array($answer)) {
            return response()->json([
                "message" => "Answer must be an array",
            ], 422);
        }

        $entityQuiz = EntityQuiz::findOrFail($response->entity_quiz_id);
        $check = $question->check($answer);
        $oldPoints = $response->points;
        $newPoints = $check->points;
        $deltaPoints = $newPoints - $oldPoints;

        \DB::beginTransaction();

        $response->answer = $check->response;
        $response->is_correct = $check->isCorrect;
        $response->points = $newPoints;

        $entityQuiz->points += $deltaPoints;

        $response->save();
        $entityQuiz->save();

        \DB::commit();

        return response()->json([
            "message" => "Response updated",
            "entity_id" => $entityQuiz->entity_id,
            "quiz_id" => $question->quiz_id,
            "question_id" => $response->question_id,
            "old_points" => $oldPoints,
            "new_points" => $newPoints,
            "delta_points" => $deltaPoints,
            "is_correct" => $response->is_correct,
            "answer" => $response->answer,
        ]);
    }

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
