<?php

use App\Http\Middleware\EnsureMember;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureRole::class,
            'staff' => EnsureStaff::class,
            'member' => EnsureMember::class,
        ]);

        $middleware->redirectGuestsTo(fn ($request) => $request->is('api/*') ? null : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'unauthenticated', 'message' => 'Non authentifié.'], 401);
            }
        });

        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'not_found', 'message' => 'Ressource introuvable.'], 404);
            }
        });
    })->create();
