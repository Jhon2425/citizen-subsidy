<x-guest-layout>

    @php
        // Same logo lookup as the welcome page: public/images/ first, public/build/ as a fallback.
        $logo = collect(['images/LGU-LOGO.svg', 'build/LGU-LOGO.svg'])
            ->first(fn ($path) => file_exists(public_path($path))) ?? 'images/LGU-LOGO.svg';

        // The Register button/link only shows if the register route exists.
        $canRegister = Route::has('register');

        // Palette (taken from the LGU seal, shared with the welcome page):
        //   forest green #2f6b1f / #245217 / #1f4a12, light tints #eef5ea / #dcebd3,
        //   brick red #924337, tan gold #bb8f63.
    @endphp

    <style>[x-cloak] { display: none !important; }</style>

    <div class="w-full max-w-md mx-auto"
         x-data="{ showLoginPassword: false }">

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xl shadow-[#1f4a12]/10 overflow-hidden">

            {{-- =================================================
                BRANDED HEADER
            ================================================== --}}

            <div class="relative overflow-hidden
                        bg-gradient-to-br from-[#1f4a12] via-[#2f6b1f] to-[#3d7a26]
                        px-6 pt-8 pb-7 sm:px-8 text-center">

                {{-- dot pattern + soft circles --}}
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
                        Sign in to access your dashboard
                    </p>

                </div>

            </div>


            <div class="px-6 pt-6 pb-8 sm:px-8">

                {{-- =============================================
                    LOG IN / REGISTER SWITCH
                    "Log in" is the current page; "Register" goes to register.blade.php
                ============================================== --}}
                @if ($canRegister)
                    <nav aria-label="Log in or register"
                         class="grid grid-cols-2 gap-1 rounded-xl bg-gray-100 p-1">

                        <span aria-current="page"
                              class="rounded-lg py-2 text-center text-sm font-semibold bg-white text-[#2f6b1f] shadow-sm">
                            {{ __('Log in') }}
                        </span>

                        <a href="{{ route('register') }}"
                           class="rounded-lg py-2 text-center text-sm font-semibold text-gray-500 hover:text-gray-700 transition
                                  focus:outline-none focus-visible:ring-2 focus-visible:ring-[#3d7a26]">
                            {{ __('Register') }}
                        </a>

                    </nav>
                @endif

                <x-validation-errors class="mt-5 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700" />

                @session('status')
                    <div class="mt-5 flex items-center gap-2 rounded-lg bg-[#eef5ea] border border-[#c9dfbd] px-4 py-3 text-sm font-medium text-[#245217]">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="m9 12 2 2 4-4"/>
                        </svg>
                        {{ $value }}
                    </div>
                @endsession


                {{-- =============================================
                    LOG IN FORM
                ============================================== --}}
                <div class="mt-6">

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        {{-- Email --}}
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
                                         type="email" name="email" :value="old('email')"
                                         required autofocus autocomplete="username"
                                         placeholder="you@example.com" />
                            </div>
                        </div>

                        {{-- Password --}}
                        <div>
                            <div class="flex items-center justify-between">
                                <x-label for="password" value="{{ __('Password') }}" class="text-sm font-semibold text-gray-700" />

                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}"
                                       class="text-xs font-semibold text-[#2f6b1f] hover:text-[#245217] hover:underline
                                              rounded focus:outline-none focus-visible:ring-2
                                              focus-visible:ring-offset-2 focus-visible:ring-[#3d7a26]">
                                        {{ __('Forgot password?') }}
                                    </a>
                                @endif
                            </div>

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
                                         x-bind:type="showLoginPassword ? 'text' : 'password'"
                                         name="password" required autocomplete="current-password"
                                         placeholder="••••••••" />

                                <button type="button"
                                        @click="showLoginPassword = !showLoginPassword"
                                        class="absolute inset-y-0 right-0 flex items-center px-3
                                               text-gray-400 hover:text-[#2f6b1f] focus:outline-none"
                                        tabindex="-1"
                                        aria-label="Toggle password visibility">
                                    <svg x-show="!showLoginPassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg x-show="showLoginPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                        <path d="M6.61 6.61A13.53 13.53 0 0 0 2 11s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                        <path d="M2 2l20 20"/>
                                        <path d="M9.53 9.53a3 3 0 0 0 4.24 4.24"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Remember me --}}
                        <div class="flex items-center justify-between">
                            <label for="remember_me" class="flex items-center select-none cursor-pointer">
                                <x-checkbox id="remember_me" name="remember"
                                            class="rounded !text-[#2f6b1f] focus:!ring-[#2f6b1f]" />
                                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                            </label>
                        </div>

                        {{-- Submit --}}
                        <x-button class="w-full justify-center !bg-[#2f6b1f] hover:!bg-[#245217] active:!bg-[#1f4a12]
                                          focus:!bg-[#245217] focus:!ring-[#3d7a26] focus:!ring-offset-2
                                          !rounded-lg py-3 font-semibold tracking-wide shadow-sm">
                            {{ __('Log in') }}
                        </x-button>

                    </form>

                    @if ($canRegister)
                        <p class="mt-6 text-center text-sm text-gray-600">
                            No account yet?
                            <a href="{{ route('register') }}"
                               class="rounded font-semibold text-[#2f6b1f] hover:text-[#245217] hover:underline
                                      focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#3d7a26]">
                                Register
                            </a>
                        </p>
                    @endif

                </div>

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