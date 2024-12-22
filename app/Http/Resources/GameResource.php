<?php

namespace App\Http\Resources;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Game|self $this */

        return [
            "id" => $this->id,
            "name" => $this->name,
            "slug" => $this->slug,
            "picture" => $this->getLink($this->picture),
            "data" => $this->data,

            "groups" => $this->whenLoaded(
                "groups",
                fn() => GroupResource::collection($this->groups),
            ),
        ];
    }
}
