<?php

namespace App\Http\Resources;

use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Entity|self $this */

        return [
            "id" => $this->id,
            "name" => $this->name,
            "data" => $this->data,
            "group" => $this->when(
                $this->relationLoaded("group"),
                fn() => new GroupResource($this->group),
            ),
            "created_at" => $this->created_at->format("Y-m-d H:i:s"),
            "updated_at" => $this->updated_at->format("Y-m-d H:i:s"),
        ];
    }
}
