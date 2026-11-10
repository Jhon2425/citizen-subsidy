<?php

namespace App\Listeners;

use App\Models\LoginHistory;
use Illuminate\Auth\Events\Logout;

class RecordLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        $id = request()->hasSession() ? request()->session()->get('login_history_id') : null;

        $query = LoginHistory::query()
            ->where('user_id', $event->user->getAuthIdentifier())
            ->whereNull('logged_out_at');

        $id ? $query->whereKey($id) : $query->latest('logged_in_at')->limit(1);

        $query->update(['logged_out_at' => now()]);
    }
}