<x-guest-layout>

    <style>[x-cloak] { display: none !important; }</style>

    <div class="w-full max-w-md mx-auto">

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xl shadow-[#1f4a12]/10 overflow-hidden">

            <x-auth-brand-header subtitle="Forgot your password?" />

            <div class="px-6 pt-6 pb-8 sm:px-8">

                <p class="text-sm text-gray-600 leading-relaxed">
                    Enter the email address of your account. We will send a 6-digit verification code to it.
                </p>

                <x-validation-errors class="mt-5 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700" />

                @session('status')
                    <div class="mt-5 rounded-lg bg-[#eef5ea] border border-[#c9dfbd] px-4 py-3 text-sm font-medium text-[#245217]">
                        {{ $value }}
                    </div>
                @endsession

                <form method="POST" action="{{ route('password.email') }}" class="mt-5 space-y-5">
                    @csrf

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
                                     placeholder="you@gmail.com" />
                        </div>
                    </div>

                    <x-button class="w-full justify-center !bg-[#2f6b1f] hover:!bg-[#245217] active:!bg-[#1f4a12]
                                      focus:!bg-[#245217] focus:!ring-[#3d7a26] focus:!ring-offset-2
                                      !rounded-lg py-3 font-semibold tracking-wide shadow-sm">
                        {{ __('Send verification code') }}
                    </x-button>
                </form>

                <p class="mt-6 text-center text-sm text-gray-600">
                    <a href="{{ route('login') }}"
                       class="rounded font-semibold text-[#2f6b1f] hover:text-[#245217] hover:underline
                              focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#3d7a26]">
                        &larr; Back to log in
                    </a>
                </p>

            </div>

        </div>

    </div>

</x-guest-layout>