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
            "data" => $this->data,
            "answers" => $this->when(
                $request->has("withAnswers"),
                $this->getAnswers(),
            ),
            "options" => $this->when(
                $this->relationLoaded("options"),
                function () {
                    if (!$this->type->showOptions()) {
                        return [];
                    }

                    return $this->options->mapWithKeys(
                        fn($option) => [
                            $option->order => OptionResource::make($option),
                        ],
                    );
                },
            ),
        ];
    }
}
