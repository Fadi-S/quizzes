<?php

namespace App\Http\Resources;

use App\Models\EntityQuiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /* @var EntityQuiz|self $this */

        return [
            "id" => $this->id,
            "entity" => $this->whenLoaded(
                "entity",
                fn() => EntityResource::make($this->entity),
            ),
            "quiz" => $this->whenLoaded(
                "quiz",
                fn() => QuizResource::make($this->quiz),
            ),
            "points" => $this->points,
            "created_at" => $this->created_at?->format("Y-m-d H:i:s"),

            "answers" => $this->whenLoaded(
                "answers",
                fn() => (object) $this->answers
                    ->mapWithKeys(function ($answer) {
                        $data = [
                            "id" => $answer->id,
                            "question_id" => $answer->question_id,
                            "is_correct" => $answer->is_correct,
                            "answer" => $answer->answer,
                            "points" => $answer->points,
                        ];

                        if ($answer->relationLoaded("question")) {
                            $data["question"] = QuestionResource::make(
                                $answer->question,
                            );
                        }

                        return [$answer->question_id => $data];
                    })
                    ->all(),
            ),
        ];
    }
}
