<x-guest-layout>

    <style>[x-cloak] { display: none !important; }</style>

    <div class="w-full max-w-md mx-auto" x-data="{ code: '' }">

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xl shadow-[#1f4a12]/10 overflow-hidden">

            <x-auth-brand-header subtitle="Enter your verification code" />

            <div class="px-6 pt-6 pb-8 sm:px-8">

                <p class="text-sm text-gray-600 leading-relaxed">
                    We sent a 6-digit code to
                    <span class="font-semibold text-gray-800">{{ $maskedEmail }}</span>.
                    Enter it below to continue. The code expires in 10 minutes.
                </p>

                <x-validation-errors class="mt-5 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700" />

                @session('status')
                    <div class="mt-5 rounded-lg bg-[#eef5ea] border border-[#c9dfbd] px-4 py-3 text-sm font-medium text-[#245217]">
                        {{ $value }}
                    </div>
                @endsession

                <form method="POST" action="{{ route('password.verify.submit') }}" class="mt-5 space-y-5">
                    @csrf

                    <div>
                        <x-label for="code" value="{{ __('Verification code') }}" class="text-sm font-semibold text-gray-700" />

                        <x-input id="code"
                                 class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm text-center text-2xl font-semibold
                                        tracking-[0.5em] focus:!border-[#2f6b1f] focus:!ring-[#2f6b1f] focus:!ring-1"
                                 type="text" name="code"
                                 inputmode="numeric" autocomplete="one-time-code"
                                 maxlength="6" required autofocus
                                 x-model="code"
                                 x-on:input="code = code.replace(/\D/g, '').slice(0, 6)"
                                 placeholder="------" />
                    </div>

                    <x-button x-bind:disabled="code.length !== 6"
                              class="w-full justify-center !bg-[#2f6b1f] hover:!bg-[#245217] active:!bg-[#1f4a12]
                                     focus:!bg-[#245217] focus:!ring-[#3d7a26] focus:!ring-offset-2
                                     !rounded-lg py-3 font-semibold tracking-wide shadow-sm
                                     disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('Verify code') }}
                    </x-button>
                </form>

                <form method="POST" action="{{ route('password.resend') }}" class="mt-5 text-center text-sm text-gray-600">
                    @csrf
                    Didn't get the code?
                    <button type="submit"
                            class="rounded font-semibold text-[#2f6b1f] hover:text-[#245217] hover:underline
                                   focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#3d7a26]">
                        Resend code
                    </button>
                    <p class="mt-1 text-xs text-gray-400">Check your spam folder too. You can request a new code once a minute.</p>
                </form>

                <p class="mt-6 text-center text-sm text-gray-600">
                    <a href="{{ route('password.request') }}"
                       class="rounded font-semibold text-[#2f6b1f] hover:text-[#245217] hover:underline
                              focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#3d7a26]">
                        Use a different email
                    </a>
                    &middot;
                    <a href="{{ route('login') }}"
                       class="rounded font-semibold text-[#2f6b1f] hover:text-[#245217] hover:underline
                              focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#3d7a26]">
                        Back to log in
                    </a>
                </p>

            </div>

        </div>

    </div>

</x-guest-layout>