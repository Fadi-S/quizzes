<?php

namespace App\Http\Resources;

use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Option|OptionResource $this */

        return [
            "id" => $this->id,
            "order" => $this->order,
            "name" => $this->name,
            "picture" => $this->getLink($this->picture),

            "question" => $this->whenLoaded("question", function () {
                return QuestionResource::make($this->question);
            }),
        ];
    }
}
