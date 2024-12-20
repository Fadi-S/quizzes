<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
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
            "name" => $this->name,
            "data" => $this->data,

            "game" => $this->whenLoaded(
                "game",
                fn() => GameResource::make($this->game),
            ),
            "entities" => $this->whenLoaded(
                "entities",
                fn() => EntityResource::collection($this->entities),
            ),
            "quizzes" => $this->whenLoaded(
                "quizzes",
                fn() => QuizResource::collection($this->quizzes),
            ),
        ];
    }
}
