<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\PermissionMiddleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as RouteServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register permission middleware
         $router = $this->app['router'];
        $router->aliasMiddleware('permission', PermissionMiddleware::class);

        // Rate Limiting
        $this->configureRateLimiting();
        //parent::boot();
    }

    protected function configureRateLimiting(): void
    {
        // General reads: list/show
        RateLimiter::for('orders-read', function (Request $request) {
            $key = optional($request->user())->id ?: $request->ip();
            return Limit::perMinute(120)->by($key)->response(function () {
                return response()->json(['error' => 'Too many requests (read).'], 429);
            });
        });

        // Writes: create + transitions (tighter)
        RateLimiter::for('orders-write', function (Request $request) {
            $userKey = (optional($request->user())->id ?: $request->ip());

            // also include order id when present to stop per-order spamming
            $orderId = (string) ($request->route('id') ?? 'new');

            return [
                // Global per-user/IP write ceiling
                Limit::perMinute(20)->by($userKey)->response(function () {
                    return response()->json(['error' => 'Too many requests (write).'], 429);
                }),

                // Per-order limiter to stop repeated hits to the same order
                Limit::perMinute(10)->by($userKey.':order:'.$orderId)->response(function () {
                    return response()->json(['error' => 'Too many requests for this order.'], 429);
                }),
            ];
        });
    }
}
