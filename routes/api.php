<?php

use App\Http\Controllers\Api\UnityLoginController;
use Illuminate\Support\Facades\Route;


Route::post('/unity-login', [UnityLoginController::class, 'login']);
