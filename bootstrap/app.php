<?php
if (!function_exists('fake')) {
    function fake($locale = null) {
        return \Faker\Factory::create($locale ?? config('app.faker_locale', 'en_US'));
    }
}
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
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'verified.profile' => \App\Http\Middleware\EnsureProfileIsVerified::class,
        ]);
        
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\AuditLogMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->wantsJson();
        });
        
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.', 'success' => false], 401);
            }
        });
        
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $user = $request->user();
                $data = [
                    'message' => $e->getMessage(),
                    'user_id' => $user ? $user->id : null,
                    'user_email' => $user ? $user->email : null,
                    'roles' => $user ? $user->roles()->get()->toArray() : [],
                    'hasAnyRoleCompany' => $user ? $user->hasAnyRole(['company']) : false,
                ];
                \Illuminate\Support\Facades\Log::error('403 Unauthorized Details', $data);
                return response()->json($data, 403);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], 404);
            }
        });
        
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                \Illuminate\Support\Facades\Log::error('QueryException', ['message' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => app()->isProduction() ? 'A database error occurred.' : $e->getMessage(),
                ], 500);
            }
        });
    })->create();
