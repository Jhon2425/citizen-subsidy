<x-guest-layout>

    @php
        // Same logo lookup used on the login / welcome pages.
        $logo = collect(['images/LGU-LOGO.svg', 'build/LGU-LOGO.svg'])
            ->first(fn ($path) => file_exists(public_path($path))) ?? 'images/LGU-LOGO.svg';

        // Palette (taken from the LGU seal, shared with the login page):
        //   forest green #2f6b1f / #245217 / #1f4a12, light tints #eef5ea / #dcebd3,
        //   brick red #924337, tan gold #bb8f63.
    @endphp

    <style>[x-cloak] { display: none !important; }</style>

    <div class="w-full max-w-md mx-auto"
         x-data="{
             showPassword: false,
             showConfirm: false,

             email: @js(old('email', '')),
             password: '',
             confirm: '',

             // Email: Gmail addresses only
             get emailValid()   { return /^[A-Za-z0-9._%+\-]+@gmail\.com$/i.test(this.email); },
             get emailInvalid() { return this.email.length > 0 && !this.emailValid; },

             // Password rules
             get hasLength() { return this.password.length >= 8; },
             get hasLower()  { return /[a-z]/.test(this.password); },
             get hasUpper()  { return /[A-Z]/.test(this.password); },
             get hasNumber() { return /\d/.test(this.password); },
             get passwordValid() { return this.hasLength && this.hasLower && this.hasUpper && this.hasNumber; },

             // 0 to 4, one point each for: 8+ characters, upper and lower case, a number, a symbol.
             get score() {
                 const p = this.password;
                 if (!p) return 0;
                 return (p.length >= 8 ? 1 : 0)
                      + (/[a-z]/.test(p) && /[A-Z]/.test(p) ? 1 : 0)
                      + (/\d/.test(p) ? 1 : 0)
                      + (/[^A-Za-z0-9]/.test(p) ? 1 : 0);
             },
             get strengthLabel() { return ['', 'Weak', 'Fair', 'Good', 'Strong'][this.score]; },
             get strengthText()  { return ['', 'text-[#924337]', 'text-[#a8763f]', 'text-[#3d7a26]', 'text-[#245217]'][this.score]; },
             barColor(i) {
                 if (i > this.score) return 'bg-gray-200';
                 return ['', 'bg-[#924337]', 'bg-[#bb8f63]', 'bg-[#4f9033]', 'bg-[#2f6b1f]'][this.score];
             },
             get matches() { return this.confirm.length > 0 && this.password === this.confirm; },
             get mismatch() { return this.confirm.length > 0 && this.password !== this.confirm; },

             // Submit is enabled only when everything is valid
             get canSubmit() { return this.emailValid && this.passwordValid && this.matches; }
         }">

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xl shadow-[#1f4a12]/10 overflow-hidden">

            {{-- =================================================
                BRANDED HEADER — echoes the login page hero banner
            ================================================== --}}

            <div class="relative overflow-hidden
                        bg-gradient-to-br from-[#1f4a12] via-[#2f6b1f] to-[#3d7a26]
                        px-6 pt-8 pb-7 sm:px-8 text-center">

                <div class="absolute inset-0 opacity-[0.10]" aria-hidden="true"
                     style="background-image: radial-gradient(circle, #fff 1px, transparent 1px);
                            background-size: 16px 16px;">
                </div>
                <div class="absolute -right-14 -top-16 w-52 h-52 rounded-full bg-white/10" aria-hidden="true"></div>
                <div class="absolute -left-16 -bottom-20 w-52 h-52 rounded-full bg-white/10" aria-hidden="true"></div>

                <div class="relative">

                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-white overflow-hidden
                                shadow-lg ring-4 ring-[#bb8f63]/70">
                        <img src="{{ asset($logo) }}"
                             alt="{{ config('app.name', 'LGU') }} Logo"
                             class="w-full h-full rounded-full object-cover" />
                    </div>

                    <h1 class="mt-4 text-white text-lg sm:text-xl font-bold leading-snug">
                        Senior Citizen Subsidy<br class="hidden sm:block"> Management System
                    </h1>

                    <span class="mx-auto mt-3 block h-1 w-10 rounded-full bg-[#bb8f63]" aria-hidden="true"></span>

                    <p class="mt-3 text-[#dcebd3] text-sm">
                        Create your account to get started
                    </p>

                </div>

            </div>


            {{-- =================================================
                FORM
            ================================================== --}}

            <div class="px-6 pt-6 pb-8 sm:px-8">

                <x-validation-errors class="mb-5 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700" />

                {{-- =============================================
                    GOOGLE SIGN-UP
                    Requires Laravel Socialite (or similar) wired up with
                    a 'auth.google.redirect' route that kicks off the
                    OAuth flow. The button is hidden automatically if
                    that route hasn't been registered yet.
                ============================================== --}}
                @if (Route::has('auth.google.redirect'))
                    <a href="{{ route('auth.google.redirect') }}"
                       class="flex w-full items-center justify-center gap-3 rounded-lg border border-gray-300
                              bg-white py-2.5 text-sm font-semibold text-gray-700 shadow-sm
                              hover:bg-gray-50 transition
                              focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#3d7a26]">
                        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.2-2.27H12v4.3h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.87c2.27-2.09 3.55-5.17 3.55-8.66Z"/>
                            <path fill="#34A853" d="M12 24c3.24 0 5.95-1.07 7.94-2.9l-3.87-3c-1.08.72-2.46 1.15-4.07 1.15-3.13 0-5.78-2.11-6.73-4.96H1.27v3.11A12 12 0 0 0 12 24Z"/>
                            <path fill="#FBBC05" d="M5.27 14.29a7.2 7.2 0 0 1 0-4.58V6.6H1.27a12 12 0 0 0 0 10.8l4-3.11Z"/>
                            <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.43-3.43C17.94 1.19 15.24 0 12 0 7.31 0 3.26 2.69 1.27 6.6l4 3.11C6.22 6.86 8.87 4.75 12 4.75Z"/>
                        </svg>
                        {{ __('Sign up with Google') }}
                    </a>

                    <div class="my-5 flex items-center gap-3" aria-hidden="true">
                        <span class="h-px flex-1 bg-gray-200"></span>
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">or sign up with email</span>
                        <span class="h-px flex-1 bg-gray-200"></span>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="space-y-5">
                    @csrf

                    {{-- Name --}}
                    <div>
                        <x-label for="name" value="{{ __('Full name') }}" class="text-sm font-semibold text-gray-700" />

                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </span>

                            <x-input id="name"
                                     class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm
                                            focus:!border-[#2f6b1f] focus:!ring-[#2f6b1f] focus:!ring-1"
                                     type="text" name="name" :value="old('name')"
                                     required autofocus autocomplete="name"
                                     placeholder="Juan Dela Cruz" />
                        </div>
                    </div>

                    {{-- Email (Gmail only) --}}
                    <div>
                        <x-label for="email" value="{{ __('Email') }}" class="text-sm font-semibold text-gray-700" />

                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                    <rect width="20" height="16" x="2" y="4" rx="2"/>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                                </svg>
                            </span>

                            <x-input id="email"
                                     class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm
                                            focus:!border-[#2f6b1f] focus:!ring-[#2f6b1f] focus:!ring-1"
                                     type="email" name="email"
                                     x-model="email"
                                     x-bind:class="emailInvalid ? '!border-[#924337]' : ''"
                                     pattern="[A-Za-z0-9._%+\-]+@gmail\.com"
                                     title="Only @gmail.com addresses are accepted"
                                     required autocomplete="username"
                                     placeholder="you@gmail.com" />
                        </div>

                        <p x-show="emailInvalid" x-cloak class="mt-1.5 text-xs font-medium text-[#924337]">
                            Only @gmail.com email addresses are accepted.
                        </p>
                    </div>

                    {{-- Password + live strength meter + requirements --}}
                    <div>
                        <x-label for="password" value="{{ __('Password') }}" class="text-sm font-semibold text-gray-700" />

                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="3" y="11" width="18" height="10" rx="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </span>

                            <x-input id="password"
                                     class="block w-full pl-10 pr-10 rounded-lg border-gray-300 shadow-sm
                                            focus:!border-[#2f6b1f] focus:!ring-[#2f6b1f] focus:!ring-1"
                                     :type="'password'"
                                     x-bind:type="showPassword ? 'text' : 'password'"
                                     x-model="password"
                                     name="password" required autocomplete="new-password"
                                     placeholder="At least 8 characters" />

                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center px-3
                                           text-gray-400 hover:text-[#2f6b1f] focus:outline-none"
                                    tabindex="-1"
                                    aria-label="Toggle password visibility">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                    <path d="M6.61 6.61A13.53 13.53 0 0 0 2 11s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                    <path d="M2 2l20 20"/>
                                    <path d="M9.53 9.53a3 3 0 0 0 4.24 4.24"/>
                                </svg>
                            </button>
                        </div>

                        <div x-show="password.length > 0" x-cloak class="mt-2">
                            <div class="grid grid-cols-4 gap-1.5" aria-hidden="true">
                                <template x-for="i in 4" :key="i">
                                    <span class="h-1.5 rounded-full transition-colors duration-200" :class="barColor(i)"></span>
                                </template>
                            </div>
                            <p class="mt-1.5 text-xs text-gray-500">
                                Password strength:
                                <span class="font-semibold" :class="strengthText" x-text="strengthLabel"></span>
                            </p>
                        </div>

                        <ul class="mt-2 space-y-1 text-xs" x-show="password.length > 0" x-cloak>
                            <li :class="hasLength ? 'text-[#2f6b1f]' : 'text-gray-500'">
                                <span x-text="hasLength ? '✓' : '○'"></span> At least 8 characters
                            </li>
                            <li :class="hasUpper ? 'text-[#2f6b1f]' : 'text-gray-500'">
                                <span x-text="hasUpper ? '✓' : '○'"></span> One uppercase letter
                            </li>
                            <li :class="hasLower ? 'text-[#2f6b1f]' : 'text-gray-500'">
                                <span x-text="hasLower ? '✓' : '○'"></span> One lowercase letter
                            </li>
                            <li :class="hasNumber ? 'text-[#2f6b1f]' : 'text-gray-500'">
                                <span x-text="hasNumber ? '✓' : '○'"></span> One number
                            </li>
                        </ul>
                    </div>

                    {{-- Confirm password + live match hint --}}
                    <div>
                        <x-label for="password_confirmation" value="{{ __('Confirm password') }}" class="text-sm font-semibold text-gray-700" />

                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>
                                    <path d="m9 12 2 2 4-4"/>
                                </svg>
                            </span>

                            <x-input id="password_confirmation"
                                     class="block w-full pl-10 pr-10 rounded-lg border-gray-300 shadow-sm
                                            focus:!border-[#2f6b1f] focus:!ring-[#2f6b1f] focus:!ring-1"
                                     :type="'password'"
                                     x-bind:type="showConfirm ? 'text' : 'password'"
                                     x-model="confirm"
                                     name="password_confirmation" required autocomplete="new-password"
                                     placeholder="Re-enter your password" />

                            <button type="button"
                                    @click="showConfirm = !showConfirm"
                                    class="absolute inset-y-0 right-0 flex items-center px-3
                                           text-gray-400 hover:text-[#2f6b1f] focus:outline-none"
                                    tabindex="-1"
                                    aria-label="Toggle password confirmation visibility">
                                <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg x-show="showConfirm" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                    <path d="M6.61 6.61A13.53 13.53 0 0 0 2 11s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                    <path d="M2 2l20 20"/>
                                    <path d="M9.53 9.53a3 3 0 0 0 4.24 4.24"/>
                                </svg>
                            </button>
                        </div>

                        <p x-show="matches" x-cloak class="mt-1.5 text-xs font-medium text-[#2f6b1f]">Passwords match.</p>
                        <p x-show="mismatch" x-cloak class="mt-1.5 text-xs font-medium text-[#924337]">Passwords do not match yet.</p>
                    </div>

                    {{-- Terms and privacy (only if Jetstream's feature is on) --}}
                    @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                        <label for="terms" class="flex items-start select-none cursor-pointer">
                            <x-checkbox name="terms" id="terms" required
                                        class="mt-0.5 rounded !text-[#2f6b1f] focus:!ring-[#2f6b1f]" />
                            <span class="ms-2 text-sm text-gray-600 leading-relaxed">
                                {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                        'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="font-semibold text-[#2f6b1f] hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#3d7a26] rounded-md">'.__('Terms of Service').'</a>',
                                        'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="font-semibold text-[#2f6b1f] hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#3d7a26] rounded-md">'.__('Privacy Policy').'</a>',
                                ]) !!}
                            </span>
                        </label>
                    @endif

                    <div class="pt-1">
                        <x-button x-bind:disabled="!canSubmit"
                                  class="w-full justify-center !bg-[#2f6b1f] hover:!bg-[#245217] active:!bg-[#1f4a12]
                                         focus:!bg-[#245217] focus:!ring-[#3d7a26] focus:!ring-offset-2
                                         !rounded-lg py-3 font-semibold tracking-wide shadow-sm
                                         disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('Create account') }}
                        </x-button>
                    </div>

                    <p class="text-center text-sm text-gray-600 pt-1">
                        {{ __('Already registered?') }}
                        <a href="{{ route('login') }}"
                           class="font-semibold text-[#2f6b1f] hover:text-[#245217] hover:underline
                                  rounded-md focus:outline-none focus-visible:ring-2
                                  focus-visible:ring-offset-2 focus-visible:ring-[#3d7a26]">
                            {{ __('Log in') }}
                        </a>
                    </p>

                </form>

            </div>

        </div>

        {{-- =================================================
            FOOTER NOTE + BACK LINK
        ================================================== --}}

        <div class="mt-6 space-y-3 text-center">

            <p class="text-xs text-gray-500 flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="11" width="18" height="10" rx="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                Authorized access only &middot; Senior Citizen Affairs
            </p>

            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-1 rounded text-xs font-semibold text-[#2f6b1f] hover:text-[#245217] hover:underline
                      focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#3d7a26]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
                Back to home
            </a>

        </div>

    </div>

</x-guest-layout>