<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/webhook/github', App\Http\Controllers\GitHubWebhookController::class . '@handle')
    ->name('webhook.github')
    ->withoutMiddleware(['auth:sanctum']);

Route::post('/webhook/livekit', App\Http\Controllers\LiveKitWebhookController::class . '@handle')
    ->name('webhook.livekit')
    ->withoutMiddleware(['auth:sanctum']);