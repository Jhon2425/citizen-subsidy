<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Jetstream\Jetstream;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Only allow @gmail.com accounts (same rule as the register form).
     * Set to false to also allow Google Workspace / other Google-managed domains.
     */
    private const GMAIL_ONLY = true;

    /**
     * Send the user to Google's consent screen.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google's response: log in an existing user or create a new one.
     */
    public function callback(Request $request)
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')
                ->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        $email = strtolower((string) $google->getEmail());

        if ($email === '') {
            return redirect()->route('login')
                ->withErrors(['email' => 'Your Google account did not return an email address.']);
        }

        if (self::GMAIL_ONLY && ! str_ends_with($email, '@gmail.com')) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Only @gmail.com accounts are accepted.']);
        }

        // Match by google_id first, then by email so existing accounts get linked.
        $user = User::where('google_id', $google->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            if ($user->google_id !== $google->getId()) {
                $user->forceFill(['google_id' => $google->getId()])->save();
            }
        } else {
            $user = DB::transaction(function () use ($google, $email) {
                $user = User::create([
                    'name'      => $google->getName() ?: Str::before($email, '@'),
                    'email'     => $email,
                    'google_id' => $google->getId(),
                    // Random, unused password: Google handles authentication.
                    'password'  => Hash::make(Str::random(32)),
                ]);

                // Jetstream teams: create the personal team like CreateNewUser does.
                if (class_exists(Jetstream::class) && Jetstream::hasTeamFeatures()) {
                    $user->ownedTeams()->save(Team::forceCreate([
                        'user_id'       => $user->id,
                        'name'          => explode(' ', $user->name, 2)[0] . "'s Team",
                        'personal_team' => true,
                    ]));
                }

                return $user;
            });
        }

        // Google already verified this address.
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home', '/dashboard'));
    }
}