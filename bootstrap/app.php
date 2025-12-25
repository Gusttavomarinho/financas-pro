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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // In production, don't render detailed exceptions to users
        // Laravel automatically hides stack traces when APP_DEBUG=false
        // This is additional safety to ensure JSON API errors are clean
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->expectsJson() && !config('app.debug')) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                return response()->json([
                    'message' => $status === 500 ? 'Erro interno do servidor.' : $e->getMessage(),
                ], $status);
            }
        });
    })->create();
