<?php

namespace App\Providers;

use App\Models\LoginHistory;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // Matches the rules shown on the register page and assumed by
        // PasswordCodeController (letters, numbers, symbols). Delete this
        // block if you only want the default minimum length.
        Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers()->symbols());

        // ---- LOGIN HISTORY ----
        Event::listen(Login::class, function (Login $event) {
            $history = LoginHistory::create([
                'user_id'      => $event->user->getAuthIdentifier(),
                'ip_address'   => request()->ip(),
                'user_agent'   => substr((string) request()->userAgent(), 0, 1000),
                'logged_in_at' => now(),
            ]);

            if (request()->hasSession()) {
                request()->session()->put('login_history_id', $history->id);
            }
        });

        Event::listen(Logout::class, function (Logout $event) {
            if (! $event->user) {
                return;
            }

            LoginHistory::where('user_id', $event->user->getAuthIdentifier())
                ->whereNull('logged_out_at')
                ->latest('logged_in_at')
                ->limit(1)
                ->update(['logged_out_at' => now()]);
        });
    }
}