<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\EntityQuestion;
use App\Models\EntityQuiz;
use App\Models\Group;
use App\Models\Quiz;
use App\Repositories\QuizRepository;
use Illuminate\Http\Request;

class SubmitQuizGuestController extends Controller
{
    public function __construct(private readonly QuizRepository $quizRepo)
    {
    }

    public function __invoke($group, $slug, Request $request)
    {
        $quiz = Quiz::fromGroupAndSlug($group, $slug);
        if (!$quiz) {
            abort(404);
        }

        $group = Group::where("slug", $group)->firstOrFail();
        $entity = Entity::createGuest($group);

        $quizResponse = $this->quizRepo->submit(
            $quiz,
            $entity,
            $request->get("questions"),
        );

        return response([
            "points" => $quizResponse->points,
        ]);
    }
}
