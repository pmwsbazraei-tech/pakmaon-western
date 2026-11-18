<?php

use App\Http\Middleware\EnsureCanManageShop;
use App\Http\Middleware\EnsureOutlet;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'outlet' => EnsureOutlet::class,
            'shop.manage' => EnsureCanManageShop::class,
        ]);

        // Callback/webhook CHIP datang dari server gateway; disahkan dengan tandatangan RSA
        $middleware->validateCsrfTokens(except: ['payments/chip/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
