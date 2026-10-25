<?php

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
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        // Forwarded headers are accepted only from the local Nginx proxy.
        // A wildcard used to trust every caller (required on Render). That is
        // unsafe once the PHP process is bound to localhost: a direct request
        // could spoof X-Forwarded-Proto. Wildcard values are ignored.
        // Console boot invokes this callback before the config repository
        // exists. Fall back to the local Nginx address in that case.
        $raw = '127.0.0.1';
        if (app()->bound('config')) {
            $raw = (string) config('projectrack.trusted_proxies', '127.0.0.1');
        }
        if (trim($raw) === '' || trim($raw) === '*') {
            $raw = '127.0.0.1';
        }
        $proxies = array_values(array_filter(array_map('trim', explode(',', $raw))));
        $middleware->trustProxies(
            at: $proxies,
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
