<?php

namespace App\Http\Controllers;

use App\Http\Resources\EntityResource;
use App\Models\Entity;
use App\Models\Group;
use Illuminate\Http\Request;

class EntityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $group = Group::query()
            ->where("slug", "=", $request->get("group"))
            ->first();

        return [
            "entities" => EntityResource::collection(
                Entity::query()
                    ->when(
                        $group,
                        fn($query) => $query->where("group_id", $group->id),
                    )
                    ->get(),
            ),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $group = Group::query()
            ->where("slug", $request->get("group"))
            ->firstOrFail();

        $entity = Entity::query()->create([
            "group_id" => $group->id,
            "name" => $request->get("name"),
            "data" => $request->get("data"),
        ]);

        return [
            "entity" => new EntityResource($entity),
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show(Entity $entity)
    {
        $entity->load("group");

        return [
            "entity" => new EntityResource($entity),
        ];
    }

    /**
     * Display the specified resource.
     */
    public function storeBulk(Request $request)
    {
        $request->validate([
            "group" => "required|string|exists:groups,slug",
            "entities" => "required|array",
            "entities.*.name" => "required|string|max:255",
            "entities.*.data" => "nullable|json",
        ]);

        $group = Group::query()
            ->where("slug", "=", $request->get("group"))
            ->firstOrFail();

        $entities = collect($request->get("entities"))->map(
            fn($entity) => new Entity([
                "name" => $entity["name"],
                "data" => $entity["data"] ?? null,
            ]),
        );

        $entities = collect($group->entities()->saveMany($entities->all()));

        return [
            "entities" => $entities->mapWithKeys(
                fn($entity) => [$entity->name => $entity->id],
            ),
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Entity $entity)
    {
        $entity->update([
            "name" => $request->get("name"),
            "data" => $request->get("data"),
        ]);

        return [
            "entity" => new EntityResource($entity),
        ];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Entity $entity)
    {
        $entity->delete();

        return response(null, 204);
    }
}
