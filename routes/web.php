<?php

use App\Http\Controllers\ServerController;
use Illuminate\Support\Facades\Route;

Route::get('/servidor', [ServerController::class, 'index']);

Route::get('/', function () {
    return view('welcome');
});
