<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Middleware\Authenticate;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocaleFromSession::class);
        $middleware->appendToPriorityList(Authenticate::class, \App\Http\Middleware\EnsureAdmin::class);
    })
    ->withExceptions()
    ->create();
