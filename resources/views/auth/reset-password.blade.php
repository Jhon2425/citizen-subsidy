<x-guest-layout>

    <style>[x-cloak] { display: none !important; }</style>

    <div class="w-full max-w-md mx-auto"
         x-data="{
             showPassword: false,
             showConfirm: false,
             password: '',
             confirm: '',

             get hasLength() { return this.password.length >= 8; },
             get hasLower()  { return /[a-z]/.test(this.password); },
             get hasUpper()  { return /[A-Z]/.test(this.password); },
             get hasNumber() { return /\d/.test(this.password); },
             get hasSymbol() { return /[^A-Za-z0-9]/.test(this.password); },
             get passwordValid() { return this.hasLength && this.hasLower && this.hasUpper && this.hasNumber && this.hasSymbol; },

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

             get matches()  { return this.confirm.length > 0 && this.password === this.confirm; },
             get mismatch() { return this.confirm.length > 0 && this.password !== this.confirm; },
             get canSubmit() { return this.passwordValid && this.matches; }
         }">

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xl shadow-[#1f4a12]/10 overflow-hidden">

            <x-auth-brand-header subtitle="Code verified. Choose a new password" />

            <div class="px-6 pt-6 pb-8 sm:px-8">

                <x-validation-errors class="mb-5 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700" />

                <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                    @csrf

                    {{-- New password + strength meter + requirements --}}
                    <div>
                        <x-label for="password" value="{{ __('New password') }}" class="text-sm font-semibold text-gray-700" />

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
                                     name="password" required autofocus autocomplete="new-password"
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
                            <li :class="hasSymbol ? 'text-[#2f6b1f]' : 'text-gray-500'">
                                <span x-text="hasSymbol ? '✓' : '○'"></span> One special character (e.g. ! @ # $ % &amp;)
                            </li>
                        </ul>
                    </div>

                    {{-- Confirm password --}}
                    <div>
                        <x-label for="password_confirmation" value="{{ __('Confirm new password') }}" class="text-sm font-semibold text-gray-700" />

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

                    <x-button x-bind:disabled="!canSubmit"
                              class="w-full justify-center !bg-[#2f6b1f] hover:!bg-[#245217] active:!bg-[#1f4a12]
                                     focus:!bg-[#245217] focus:!ring-[#3d7a26] focus:!ring-offset-2
                                     !rounded-lg py-3 font-semibold tracking-wide shadow-sm
                                     disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('Reset password') }}
                    </x-button>
                </form>

            </div>

        </div>

    </div>

</x-guest-layout>