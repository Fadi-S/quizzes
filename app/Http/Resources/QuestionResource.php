<?php

namespace App\Http\Resources;

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
        return [
            "id" => $this->id,
            "title" => $this->title,
            "picture" => $this->picture,
            "type" => $this->type,
            "data" => $this->data,
            "answers" => $this->when(
                $request->has("withAnswers"),
                $this->correct_answers,
            ),
            "options" => $this->whenLoaded("options", function () {
                return $this->options->mapWithKeys(
                    fn($option) => [
                        $option->order => OptionResource::make($option),
                    ],
                );
            }),
        ];
    }
}
