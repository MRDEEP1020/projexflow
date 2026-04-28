<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',  // ← this line must exist

        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    // bootstrap/app.php — add middleware alias
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'setOrg' => \App\Http\Middleware\SetActiveOrg::class,
            'detect.mode'   => \App\Http\Middleware\DetectUserMode::class,
            'require.admin' => \App\Http\Middleware\RequireAdmin::class,
            'set.org'       => \App\Http\Middleware\SetActiveOrg::class,
        ]);
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            'webhooks/github',
        ]);
    })



    // config/queue.php — set driver to database or redis (not sync) for jobs
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
