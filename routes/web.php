<?php

use App\Http\Controllers\StorageProxyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/storage/{path}', [StorageProxyController::class, 'serve'])
    ->where('path', '.*');
