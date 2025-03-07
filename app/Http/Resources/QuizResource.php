<?php

namespace App\Http\Resources;

use App\Models\Quiz;
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
        /* @var Quiz|self $this */

        return [
            "id" => $this->id,
            "name" => $this->name,
            "slug" => $this->slug,
            "group_id" => $this->group_id,
            "data" => $this->data,
            "published_at" => $this->published_at?->format("Y-m-d H:i:s"),
            "available_until" => $this->available_until?->format("Y-m-d H:i:s"),

            "group" => $this->whenLoaded(
                "group",
                fn() => GroupResource::make($this->group),
            ),

            "responses" => $this->whenLoaded(
                "responses",
                fn() => ResponseResource::collection($this->responses),
            ),

            "questions" => $this->whenLoaded(
                "questions",
                fn() => QuestionResource::collection($this->questions),
            ),

            "points" => $this->points ? ((int) $this->points) : 0,

            "is_solved" => $this->when(
                $this->is_solved !== null,
                fn() => $this->is_solved != 0,
            ),

            "questions_count" => $this->whenCounted(
                "questions",
                fn() => $this->questions_count,
            ),

            "points_won" => $this->points_won ?? 0,
        ];
    }
}
