<?php

use App\Http\Middleware\CheckKycStatus;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register KYC middleware for routes
        $middleware->alias([
            'kyc.check' => CheckKycStatus::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $jsonError = fn(string $message, int $status, mixed $errors = null) => response()->json(
            array_filter([
                'success' => false,
                'message' => $message,
                'errors'  => $errors,
            ], fn($v) => !is_null($v)),
            $status
        );

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) use ($jsonError) {
            return $jsonError('Validation failed.', 422, $e->errors());
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) use ($jsonError) {
            return $jsonError('Resource not found.', 404);
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) use ($jsonError) {
            return $jsonError('Unauthenticated.', 401);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e) use ($jsonError) {
            return $jsonError('Unauthorized.', 403);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) use ($jsonError) {
            return $jsonError('Route not found.', 404);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e) use ($jsonError) {
            return $jsonError('Method not allowed.', 405);
        });

    })->create();
