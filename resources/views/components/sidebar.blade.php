@php
    $navItems = [
        ['section' => 'Main Menu'],
        [
            'label'  => 'Dashboard',
            'href'   => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'icon'   => 'M3 12l9-9 9 9M5 10v10h14V10M9 20v-6h6v6',
        ],
        [
            'label'  => 'Announcements',
            'href'   => route('announcements.index'),
            'active' => request()->routeIs('announcements.*'),
            'icon'   => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        ],
        [
            'label'  => 'Applications',
            'href'   => route('applications.index'),
            'active' => request()->routeIs('applications.*'),
            'icon'   => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h6l5 5v11a2 2 0 01-2 2z',
        ],
        [
            'label'  => 'Senior Citizens',
            'href'   => route('senior-citizens.index'),
            'active' => request()->routeIs('senior-citizens.*'),
            'icon'   => 'M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-8a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 10-6 0',
        ],
        [
            'label'  => 'Subsidies',
            'href'   => route('subsidies.index'),
            'active' => request()->routeIs('subsidies.*'),
            'icon'   => 'M12 8c-3 0-5 1.5-5 3.5S9 15 12 15s5-1.5 5-3.5S15 8 12 8zm0 0V5m0 10v4',
        ],
        [
            'label'  => 'Subsidy Releases',
            'href'   => route('subsidy-releases.index'),
            'active' => request()->routeIs('subsidy-releases.*'),
            'icon'   => 'M12 8v8m-4-4h8M5 4h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1z',
        ],
        ['section' => 'Management'],
        [
            'label'  => 'Reports',
            'href'   => route('reports.index'),
            'active' => request()->routeIs('reports.*'),
            'icon'   => 'M4 19V5m0 14h16M8 16v-5m4 5V7m4 9v-8',
        ],
        [
            'label' => 'Users',
            'href'  => '#',
            'icon'  => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m8-10a4 4 0 100-8 4 4 0 000 8zm8 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75',
        ],
    ];

    $user = auth()->user();

    $sidebarClass = 'shell-sidebar fixed inset-y-0 left-0 z-40 w-72 overflow-hidden bg-[oklch(45%_0.15_151.711)] text-white border-r border-[oklch(35%_0.12_151.711)] will-change-[width,transform] transform-gpu lg:translate-x-0';

    $sidebarBind = "[sidebarCollapsed ? 'lg:w-20' : 'lg:w-72', mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full']";

    $labelBind = "sidebarCollapsed ? 'opacity-0' : 'opacity-100 delay-150'";

    $navScroll = 'flex-1 overflow-y-auto overflow-x-hidden px-3 py-5 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden';

    $linkBase = 'group flex items-center gap-3 px-4 py-3 mb-1 rounded-xl overflow-hidden transition-colors duration-200';

    $linkActive = 'bg-[oklch(79.2%_0.209_151.711)] text-[oklch(35%_0.12_151.711)] shadow-md';

    $linkIdle = 'text-white/80 hover:bg-white/10 hover:text-white';

    $accent = 'text-[oklch(79.2%_0.209_151.711)]';

    $avatarClass = 'w-10 h-10 rounded-full bg-[oklch(79.2%_0.209_151.711)] text-[oklch(35%_0.12_151.711)] flex items-center justify-center font-bold shrink-0';

    $toggleClass = 'shell-toggle hidden lg:flex fixed top-6 left-[16.75rem] z-50 items-center justify-center w-8 h-8 rounded-lg text-white/70 hover:text-white hover:bg-white/10 bg-[oklch(45%_0.15_151.711)] border border-white/20 transform-gpu';
@endphp


<aside class="{{ $sidebarClass }}" :class="{{ $sidebarBind }}">

<div class="w-72 shrink-0 h-full flex flex-col">


<div class="h-20 shrink-0 flex items-center px-4 overflow-hidden border-b border-white/10">

<a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0 flex-1" x-on:click="mobileSidebarOpen = false">

<div class="size-12 shrink-0 rounded-full bg-white flex items-center justify-center shadow-md overflow-hidden">
<img src="{{ asset('images/LGU-LOGO.svg') }}" alt="{{ config('app.name', 'LGU') }} Logo" class="size-full object-contain p-1" onerror="this.onerror=null;this.style.display='none';this.parentElement.innerHTML='<span class=&quot;text-sm font-bold text-[oklch(45%_0.15_151.711)]&quot;>LGU</span>'">
</div>

<div class="shell-label min-w-0" :class="{{ $labelBind }}" :aria-hidden="sidebarCollapsed.toString()">
<h1 class="text-sm font-bold text-white whitespace-nowrap">Senior Citizen</h1>
<p class="text-xs font-medium {{ $accent }} whitespace-nowrap">Subsidy Management</p>
</div>

</a>

</div>


<div class="{{ $navScroll }}">

@foreach ($navItems as $item)

@if (isset($item['section']))

<p class="shell-label px-3 mb-3 {{ $loop->first ? '' : 'mt-8' }} text-[10px] font-bold uppercase tracking-widest {{ $accent }} whitespace-nowrap" :class="{{ $labelBind }}" :aria-hidden="sidebarCollapsed.toString()">{{ $item['section'] }}</p>

@else

@php
    $isActive    = $item['active'] ?? false;
    $linkClass   = $linkBase . ' ' . ($isActive ? $linkActive : $linkIdle);
    $titleBind   = 'sidebarCollapsed ? ' . Illuminate\Support\Js::from($item['label']) . ' : null';
    $currentAttr = $isActive ? 'aria-current="page"' : '';
@endphp

<a href="{{ $item['href'] }}" class="{{ $linkClass }}" x-on:click="mobileSidebarOpen = false" :title="{{ $titleBind }}" {!! $currentAttr !!}>
<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" /></svg>
<span class="shell-label whitespace-nowrap" :class="{{ $labelBind }}">{{ $item['label'] }}</span>
</a>

@endif

@endforeach

</div>


<div class="border-t border-white/10 p-3 shrink-0 overflow-hidden">

<div class="flex items-center gap-3 rounded-xl p-2 overflow-hidden hover:bg-white/10 transition-colors duration-200">

<div class="{{ $avatarClass }}">{{ strtoupper(substr($user->name, 0, 1)) }}</div>

<div class="shell-label min-w-0" :class="{{ $labelBind }}" :aria-hidden="sidebarCollapsed.toString()">
<p class="text-sm font-semibold text-white truncate">{{ $user->name }}</p>
<p class="text-xs text-white/60 truncate">{{ $user->email }}</p>
</div>

</div>

</div>


</div>

</aside>


<button type="button" class="{{ $toggleClass }}" x-on:click="sidebarCollapsed = !sidebarCollapsed" :class="sidebarCollapsed ? '-translate-x-[13rem]' : 'translate-x-0'" :aria-expanded="(!sidebarCollapsed).toString()" aria-label="Toggle sidebar">
<svg class="w-4 h-4 transition-transform duration-300 ease-[cubic-bezier(0.4,0,0.2,1)]" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
</button>