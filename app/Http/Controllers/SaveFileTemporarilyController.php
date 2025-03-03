<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SaveFileTemporarilyController extends Controller
{
    public function upload()
    {
        $file = request()->file("file");

        $path = $file->store("temp");

        return response()->json([
            "path" => $path,
            "url" => \URL::temporarySignedRoute(
                "proxy",
                now()->addMinutes(60),
                [
                    "path" => $path,
                ],
            ),
        ]);
    }

    public function proxy(Request $request)
    {
        $path = $request->get("path");
        $disk = $request->get("disk");
        $storage = Storage::disk($disk);

        if (!$storage->exists($path)) {
            return response("File not found", 404);
        }

        $mimeType = $storage->mimeType($path);

        return response()->stream(
            function () use ($path, $storage) {
                $stream = $storage->readStream($path);
                fpassthru($stream);
                fclose($stream);
            },
            200,
            [
                "Content-Type" => $mimeType,
                "Content-Disposition" =>
                    'inline; filename="' . basename($path) . '"',
            ],
        );
    }

    public function url()
    {
        return [
            "url" => \URL::signedRoute("upload"),
        ];
    }
}
