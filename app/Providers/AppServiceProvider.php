<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use LogicException;

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
        $sharedCacheStores = ['database', 'dynamodb', 'memcached', 'redis'];

        if ($this->app->environment('production') && ! in_array(config('cache.default'), $sharedCacheStores, true)) {
            throw new LogicException('Production authentication rate limiting requires a shared cache store.');
        }

        RateLimiter::for('login-session', function (Request $request): Limit {
            return Limit::perMinute(30)
                ->by('login-session:'.hash('sha256', $request->session()->getId()));
        });

        RateLimiter::for('registration', function (Request $request): array {
            $email = Str::lower(trim((string) $request->input('email')));
            $sessionId = $request->session()->getId();

            return [
                Limit::perHour(5)->by('registration-email:'.hash('sha256', $email)),
                Limit::perHour(10)->by('registration-session:'.hash('sha256', $sessionId)),
            ];
        });
    }
}
