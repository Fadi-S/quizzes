<?php

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Question|QuestionResource $this */

        return [
            "id" => $this->id,
            "title" => $this->title,
            "picture" => $this->getLink($this->picture),
            "type" => $this->type,
            "points" => $this->points,
            "data" => $this->data,
            "answers" => $this->when(
                $request->has("withAnswers"),
                $this->getAnswers(),
            ),
            "options" => $this->when(
                $this->relationLoaded("options"),
                fn() => OptionResource::collection(
                    $request->has("withAnswers")
                        ? $this->options_with_answer
                        : $this->options_with_correct_order,
                ),
            ),
        ];
    }
}
