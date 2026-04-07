<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class StorageProxyController extends Controller
{
    public function serve(string $path): Response
    {
        $fullPath = storage_path('app/public/' . $path);

        if (! file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    }
}
