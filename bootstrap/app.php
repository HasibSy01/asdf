<?php

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
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // NFR-008(c): Pastikan stack trace / kueri DB tidak pernah bocor ke pengguna saat APP_DEBUG=false
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (
                !config('app.debug')
                && !$e instanceof \Illuminate\Validation\ValidationException
                && !$e instanceof \Illuminate\Auth\AuthenticationException
                && !$e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
            ) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Terjadi kesalahan sistem internal. Silakan hubungi administrator SPPG.',
                    ], 500);
                }

                return response()->view('errors.generic', [
                    'status' => 500,
                    'message' => 'Terjadi kendala saat memproses permintaan. Silakan coba beberapa saat lagi atau hubungi Administrator SPPG.',
                ], 500);
            }
        });
    })->create();
