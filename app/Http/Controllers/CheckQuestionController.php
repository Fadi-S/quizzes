<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;

class CheckQuestionController extends Controller
{
    public function index(Request $request, Quiz $quiz)
    {
        $points = 0;

        $questions = $request->get("questions");
        $checks = [];
        $answers = [];
        foreach ($quiz->questions as $question) {
            if (!isset($questions[$question->id])) {
                $checks[$question->id] = false;
                continue;
            }

            $answer = $questions[$question->id];

            $check = $question->check($answer);
            $answers[$question->id] = $question->getAnswers();

            $checks[$question->id] = $check;

            if ($check) {
                $points += $question->points;
            }
        }

        $data = [
            "points" => $points,
            "correct" => $checks,
        ];

        if ($request->has("withAnswers")) {
            $data["correct_answers"] = $answers;
        }

        return $data;
    }

    public function show(Request $request, Question $question)
    {
        $check = $question->check($request->get("answer"));

        $data = [
            "correct" => $check,
            "points" => $check ? $question->points : 0,
        ];

        if ($request->has("withAnswers")) {
            $data["correct_answer"] = $question->getAnswers();
        }

        return $data;
    }
}
