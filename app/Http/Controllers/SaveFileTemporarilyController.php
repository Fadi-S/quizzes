<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SaveFileTemporarilyController extends Controller
{
    public function upload()
    {
        $file = request()->file("file");

        $path = $file->store("temp");

        return response()->json([
            "path" => $path,
        ]);
    }

    public function url()
    {
        return [
            "url" => \URL::signedRoute("upload"),
        ];
    }
}
