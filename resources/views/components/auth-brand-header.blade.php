@props(['subtitle' => ''])

@php
    // Same logo lookup as the login / register pages.
    $logo = collect(['images/LGU-LOGO.svg', 'build/LGU-LOGO.svg'])
        ->first(fn ($path) => file_exists(public_path($path))) ?? 'images/LGU-LOGO.svg';
@endphp

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

        <p class="mt-3 text-[#dcebd3] text-sm">{{ $subtitle }}</p>

    </div>

</div>