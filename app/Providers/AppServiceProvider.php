<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\ExerciseDocument;
use App\Models\FeatureFlag;
use App\Models\Formula;
use App\Models\MathLevel;
use App\Models\MathLevelQuestion;
use App\Models\MathPath;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\ExerciseDocumentPolicy;
use App\Policies\FeatureFlagPolicy;
use App\Policies\FormulaPolicy;
use App\Policies\MathLevelPolicy;
use App\Policies\MathLevelQuestionPolicy;
use App\Policies\MathPathPolicy;
use App\Policies\PlatformSettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(MathPath::class, MathPathPolicy::class);
        Gate::policy(MathLevel::class, MathLevelPolicy::class);
        Gate::policy(MathLevelQuestion::class, MathLevelQuestionPolicy::class);
        Gate::policy(Formula::class, FormulaPolicy::class);
        Gate::policy(ExerciseDocument::class, ExerciseDocumentPolicy::class);
        Gate::policy(FeatureFlag::class, FeatureFlagPolicy::class);
        Gate::policy(PlatformSetting::class, PlatformSettingPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill([
                    'last_login_at' => now(),
                    'last_active_at' => now(),
                ])->saveQuietly();
            }
        });

        RateLimiter::for('cms-level-preview', function (Request $request) {
            $userKey = (string) optional($request->user())->id;
            $levelKey = (string) $request->route()->parameter('level');

            return Limit::perMinute(3)->by($userKey.'|'.$levelKey.'|'.$request->ip());
        });

        RateLimiter::for('cms-ai-test', fn (Request $request) => Limit::perMinute(5)
            ->by((string) $request->user()?->id.'|'.$request->ip()));

        RateLimiter::for('cms-global-search', fn (Request $request) => Limit::perMinute(60)
            ->by((string) $request->user()?->id.'|'.$request->ip()));

        RateLimiter::for('internal-api', fn (Request $request) => Limit::perMinute(120)
            ->by((string) $request->ip()));

        Vite::prefetch(concurrency: 3);
    }
}
