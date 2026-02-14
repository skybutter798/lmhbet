<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // ✅ Web middleware stack (runs on every normal page)
        $middleware->web(append: [
            \App\Http\Middleware\GoogleAutoTranslateHtml::class,
        ]);

        // ✅ Aliases
        $middleware->alias([
            'dbox.sig'     => \App\Http\Middleware\VerifyDBOXSignature::class,
            'dbox.inlog'   => \App\Http\Middleware\LogDBOXIncoming::class,
            'admin.auth'   => \App\Http\Middleware\AdminAuth::class,
            'log.incoming' => \App\Http\Middleware\LogIncomingRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
