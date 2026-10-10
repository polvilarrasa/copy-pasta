<?php

namespace App\Providers;

use App\Listeners\CreateDefaultFolder;
use App\Listeners\ForgetUnreadNotificationCount;
use App\Models\User;
use App\Policies\DatabaseNotificationPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Mechanisms\FrontendAssets\FrontendAssets;

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
        $this->configureDefaults();
        $this->configureGates();
        $this->configureListeners();
        $this->configureRateLimiting();
        $this->configureBlade();
    }

    /**
     * Named Blade directives. The theme directive writes the stored preference onto the <html> element, so pages
     * paint in the right mode without a script.
     */
    protected function configureBlade(): void
    {
        Blade::directive('themeAttributes', fn (): string => '<?php echo app(\App\Support\ThemePreference::class)->htmlAttributes(); ?>');
    }

    /**
     * Named limiters used by routes; votes are capped at 60 per minute per member.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('votes', fn (Request $request): Limit => Limit::perMinute(60)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }

    /**
     * Register the application's event listeners.
     */
    protected function configureListeners(): void
    {
        Event::listen(Registered::class, CreateDefaultFolder::class);
        Event::listen(NotificationSent::class, ForgetUnreadNotificationCount::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        // Deferred so the page paints before Livewire runs; Vite's module script in the head still runs first.
        app(FrontendAssets::class)->useScriptTagAttributes(['defer' => true]);

        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

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

    /**
     * Define the application's authorization gates.
     */
    protected function configureGates(): void
    {
        Gate::define('access-admin', fn (User $user): bool => $user->isStaff());

        Gate::define('manage-users', fn (User $user): bool => $user->isAdmin());

        Gate::policy(DatabaseNotification::class, DatabaseNotificationPolicy::class);
    }
}
