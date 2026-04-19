<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\ChatController;


require __DIR__.'/api.php';

// Route::post('chat', [ChatController::class, 'send']);


Route::get('/', function () {
    return view('welcome');
});
