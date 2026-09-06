<?php

namespace App\Providers;

use App\Contracts\WhatsAppService;
use App\Services\WhatsAppCloudService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WhatsAppService::class, WhatsAppCloudService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);
        app()->setLocale('es');
        Carbon::setLocale('es');
        CarbonImmutable::setLocale('es');

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        RateLimiter::for('whatsapp-marketing', fn (): Limit => Limit::perMinute(
            max(1, (int) config('services.whatsapp.marketing_rate_per_minute', 60)),
        )->by((string) (config('services.whatsapp.phone_number_id') ?: 'whatsapp')));

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
