<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-storage', function () {
    $path = storage_path('app/public');
    return [
        'storage_path' => $path,
        'exists'       => file_exists($path),
        'writable'     => is_writable($path),
        'avatars'      => file_exists($path.'/avatars') ? scandir($path.'/avatars') : 'avatars dir missing',
    ];
});

Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);

    if (! file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*');
