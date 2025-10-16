<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckRole; // Ensure this is imported

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // FIX: Added the API routing line here. This tells Laravel to load routes/api.php
        api: __DIR__.'/../routes/api.php', 
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ... other configuration ...

        $middleware->alias([
            // Your RBAC alias
            'role' => CheckRole::class, 
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();