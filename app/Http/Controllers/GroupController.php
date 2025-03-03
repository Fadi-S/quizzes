<?php

namespace App\Http\Controllers;

use App\Http\Resources\GroupResource;
use App\Models\Game;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    public function index()
    {
        return response()->json([
            "groups" => GroupResource::collection(Group::all()),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "name" => [
                "required",
                "string",
                "max:255",
                Rule::unique("groups")->where("game_id", Game::current()->id),
            ],
            "data" => ["nullable", "array"],
        ]);

        $group = Group::create($validated);

        return response()->json([
            "message" => "Group created",
            "group" => GroupResource::make($group),
        ]);
    }

    public function show(Request $request, $group)
    {
        $group = Group::query()
            ->where("id", "=", $group)
            ->orWhere("slug", "=", $group)
            ->with([
                "quizzes" => fn($query) => $query
                    ->when(
                        $request->has("published"),
                        fn($q) => $q->published(),
                    )
                    ->when(
                        $request->has("withQuestions"),
                        fn($q) => $q->with("questions.options"),
                    ),
            ])
            ->firstOrFail();

        return response()->json(["group" => GroupResource::make($group)]);
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            "name" => [
                "required",
                "string",
                "max:255",
                Rule::unique("groups")
                    ->ignore($group->id)
                    ->where("game_id", Game::current()->id),
            ],
            "data" => ["nullable", "array"],
        ]);

        $group->update($validated);

        return response()->json([
            "message" => "Group updated",
            "group" => GroupResource::make($group),
        ]);
    }

    public function destroy(Group $group)
    {
        $group->delete();

        return response()->json([
            "message" => "Group deleted",
            "group" => GroupResource::make($group),
        ]);
    }
}
