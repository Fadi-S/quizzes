<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
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
            "group_id" => $this->group_id,
            "data" => $this->data,

            "group" => $this->whenLoaded(
                "group",
                fn() => GroupResource::make($this->group),
            ),

            "questions" => $this->whenLoaded(
                "questions",
                fn() => QuestionResource::collection($this->questions),
            ),
        ];
    }
}
