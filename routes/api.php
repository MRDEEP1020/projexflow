<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GitHubWebhookController;
use App\Http\Controllers\LiveKitWebhookController;

Route::post('/webhook/github',  [GitHubWebhookController::class,  'handle'])->name('webhook.github');
Route::post('/webhook/livekit', [LiveKitWebhookController::class, 'handle'])->name('webhook.livekit');