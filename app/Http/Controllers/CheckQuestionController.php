<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;

class CheckQuestionController extends Controller
{
    public function index(Request $request, Quiz $quiz)
    {
        $questions = $request->get("questions");
        $data = $quiz->correct($questions)->toArray();

        if (!$request->has("withAnswers")) {
            unset($data["correct_answers"]);
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
