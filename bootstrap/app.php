<?php

use App\Http\Middleware\CachePublicPage;
use App\Http\Middleware\CaptureAttribution;
use App\Http\Middleware\SecurityHeaders;
use App\Services\Seo\RedirectManager;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            CaptureAttribution::class,
        ]);

        $middleware->alias([
            'page-cache' => CachePublicPage::class,
        ]);

        // Analytics cookies are set by Google and Meta scripts, not Laravel, so they are not encrypted.
        $middleware->encryptCookies(except: ['_ga', '_fbp', '_fbc']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Before showing "page not found": follow a redirect if one exists, otherwise log the address.
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return app(RedirectManager::class)->handleMissing($request);
        });
    })->create();
