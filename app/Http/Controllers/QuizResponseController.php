<?php

namespace App\Http\Controllers;

use App\Http\Resources\EntityResource;
use App\Http\Resources\QuestionResource;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizResponseController extends Controller
{
    public function __invoke($group, $slug)
    {
        $quiz = Quiz::fromGroupAndSlug($group, $slug);
        if (!$quiz) {
            abort(404);
        }

        $quiz->load("responses.answers");

        $data = [];
        $responses = [];

        foreach ($quiz->responses as $response) {
            $answers = $response->answers->keyBy("question_id");
            $responses[$response->entity_id] = [
                "entity" => EntityResource::make($response->entity),
                "answers" => [],
                "points" => $response->points,
            ];

            foreach ($quiz->questions as $question) {
                $answer = $answers[$question->id] ?? null;

                $responses[$response->entity_id]["answers"][$question->id] = [
                    "answer" => $answer?->answer,
                    "points" => $answer?->points ?? 0,
                    "is_correct" => $answer?->is_correct ?? false,
                ];
            }
        }

        $data["responses"] = $responses;
        $data["questions"] = QuestionResource::collection($quiz->questions);

        return $data;
    }
}
