<?php

namespace App\Listeners;

use App\Models\LoginHistory;
use Illuminate\Auth\Events\Login;

class RecordLogin
{
    public function handle(Login $event): void
    {
        $history = LoginHistory::create([
            'user_id'      => $event->user->getAuthIdentifier(),
            'ip_address'   => request()->ip(),
            'user_agent'   => substr((string) request()->userAgent(), 0, 1000),
            'logged_in_at' => now(),
        ]);

        // Remember this row so the logout listener can close it.
        if (request()->hasSession()) {
            request()->session()->put('login_history_id', $history->id);
        }
    }
}