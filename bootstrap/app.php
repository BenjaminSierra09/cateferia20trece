<?php

use App\Http\Middleware\EnsureDashboardAdmin;
use App\Http\Middleware\EnsureWhatsAppAdmin;
use App\Http\Middleware\EnsureWorkSessionIsConfirmed;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('whatsapp-app', 'whatsapp-app/*')
            ? route('whatsapp.pwa.login')
            : route('login'));

        $middleware->alias([
            'dashboard.admin' => EnsureDashboardAdmin::class,
            'whatsapp.admin' => EnsureWhatsAppAdmin::class,
            'work.session' => EnsureWorkSessionIsConfirmed::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
