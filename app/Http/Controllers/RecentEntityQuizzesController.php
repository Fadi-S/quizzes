<?php

namespace App\Http\Controllers;

use App\Models\EntityQuiz;
use Illuminate\Http\Request;

class RecentEntityQuizzesController extends Controller
{
    public function __invoke(Request $request)
    {
        $since = $request->get('since', now()->subDay()->toDateTimeString());

        $responses = EntityQuiz::query()
            ->where('created_at', '>=', $since)
            ->with('quiz')
            ->get()
            ->map(fn(EntityQuiz $response) => [
                'entity_id' => $response->entity_id,
                'quiz_id' => $response->quiz_id,
                'points' => $response->points,
                'created_at' => $response->created_at->toDateTimeString(),
                'quiz' => $response->quiz ? [
                    'id' => $response->quiz->id,
                    'slug' => $response->quiz->slug,
                    'data' => $response->quiz->data,
                ] : null,
            ]);

        return response()->json([
            'responses' => $responses,
        ]);
    }
}
