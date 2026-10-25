<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Forgot password with a 6-digit verification code:
 *   1. enter email  ->  2. enter the emailed code  ->  3. set a new password
 */
class PasswordCodeController extends Controller
{
    private const CODE_TTL_MINUTES = 10;   // how long a code stays valid
    private const MAX_ATTEMPTS     = 5;    // wrong guesses before the code is thrown away
    private const RESEND_SECONDS   = 60;   // wait time between code emails

    /* -------------------------------------------------
     | Step 1: ask for the email
     ------------------------------------------------- */
    public function showRequest()
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request)
    {
        $data  = $request->validate(['email' => ['required', 'email']]);
        $email = Str::lower($data['email']);

        $request->session()->put('reset_email', $email);
        $request->session()->forget('reset_verified_email');

        $this->issueCode($email);

        // Same message whether or not the email exists, so nobody can probe for accounts.
        return redirect()->route('password.verify')->with(
            'status',
            'If that email is registered, a 6-digit code has been sent. It expires in ' . self::CODE_TTL_MINUTES . ' minutes.'
        );
    }

    /* -------------------------------------------------
     | Step 2: verify the code
     ------------------------------------------------- */
    public function showVerify(Request $request)
    {
        $email = $request->session()->get('reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $at     = strpos($email, '@') ?: strlen($email);
        $masked = Str::mask($email, '*', min(2, $at), max($at - 2, 0));

        return view('auth.verify-code', ['maskedEmail' => $masked]);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $email = $request->session()->get('reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $key    = $this->codeKey($email);
        $record = Cache::get($key);

        if (! $record) {
            throw ValidationException::withMessages([
                'code' => 'This code is invalid or has expired. Please request a new one.',
            ]);
        }

        if ($record['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($key);

            throw ValidationException::withMessages([
                'code' => 'Too many wrong attempts. Please request a new code.',
            ]);
        }

        if (! Hash::check($request->input('code'), $record['hash'])) {
            $record['attempts']++;
            Cache::put($key, $record, Carbon::createFromTimestamp($record['expires_at']));

            throw ValidationException::withMessages([
                'code' => 'Incorrect code. Please check your email and try again.',
            ]);
        }

        // Code is correct: it can't be used again.
        Cache::forget($key);
        $request->session()->put('reset_verified_email', $email);

        return redirect()->route('password.reset');
    }

    public function resend(Request $request)
    {
        $email = $request->session()->get('reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        if (Cache::has($this->cooldownKey($email))) {
            throw ValidationException::withMessages([
                'code' => 'Please wait a minute before requesting another code.',
            ]);
        }

        $this->issueCode($email);

        return back()->with('status', 'A new code has been sent if that email is registered.');
    }

    /* -------------------------------------------------
     | Step 3: set the new password (only after the code was verified)
     ------------------------------------------------- */
    public function showReset(Request $request)
    {
        if (! $request->session()->get('reset_verified_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password');
    }

    public function reset(Request $request)
    {
        $email = $request->session()->get('reset_verified_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        // Password::default() is defined in AppServiceProvider (letters, numbers, symbols).
        $request->validate([
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('password.request');
        }

        $user->forceFill([
            'password'       => Hash::make($request->input('password')),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        $request->session()->forget(['reset_email', 'reset_verified_email']);

        return redirect()->route('login')->with('status', 'Your password has been reset. You can now log in.');
    }

    /* -------------------------------------------------
     | Helpers
     ------------------------------------------------- */
    private function issueCode(string $email): void
    {
        // Start the resend cooldown even for unknown emails (keeps timing identical).
        Cache::put($this->cooldownKey($email), true, now()->addSeconds(self::RESEND_SECONDS));

        $user = User::where('email', $email)->first();

        if (! $user) {
            return;
        }

        $code    = (string) random_int(100000, 999999);
        $expires = now()->addMinutes(self::CODE_TTL_MINUTES);

        Cache::put($this->codeKey($email), [
            'hash'       => Hash::make($code),
            'attempts'   => 0,
            'expires_at' => $expires->timestamp,
        ], $expires);

        try {
            $minutes = self::CODE_TTL_MINUTES;

            Mail::raw(
                "Your verification code is: {$code}\n\n"
                . "It expires in {$minutes} minutes. If you did not request a password reset, you can ignore this email.",
                fn ($message) => $message->to($email)->subject('Your password reset code')
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function codeKey(string $email): string
    {
        return 'pwd_reset_code:' . sha1($email);
    }

    private function cooldownKey(string $email): string
    {
        return 'pwd_reset_cooldown:' . sha1($email);
    }
}