<x-app-layout>

    <style>
        :root { --brand: oklch(45% 0.15 151.711); --brand-ring: oklch(45% 0.15 151.711 / 0.25); --brand-tint: oklch(45% 0.15 151.711 / 0.08); }
        .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    </style>

    @php
        // Everything the Alpine component needs, built once on the server.
        $items = $announcements->getCollection()->mapWithKeys(fn ($a) => [
            $a->id => [
                'id'               => $a->id,
                'title'            => $a->title,
                'message'          => $a->message,
                'subsidy_id'       => (string) $a->subsidy_id,
                'subsidy'          => $a->subsidy->name ?? '—',
                'distribution_date'=> optional($a->distribution_date)->format('Y-m-d'),
                'date_label'       => optional($a->distribution_date)->format('F d, Y') ?? '—',
                'venue'            => $a->distribution_location,
                'barangay_codes'   => $a->barangays->pluck('barangay_code')->map(fn ($c) => (string) $c)->values(),
                'barangay_names'   => $a->barangays->pluck('barangay_name')->values(),
                'status'           => $a->status,
                'sent'             => $a->sent_count,
                'recipients'       => $a->recipients_count,
                'posted'           => optional($a->created_at)->diffForHumans(),
                'update_url'       => route('announcements.update', $a),
                'destroy_url'      => route('announcements.destroy', $a),
            ],
        ]);

        $config = [
            'items'       => $items,
            'storeUrl'    => route('announcements.store'),
            'countUrl'    => route('announcements.recipients-count'),
            'recipients'  => $recipientsCount ?? null,
            'barangays'   => $barangays->map(fn ($b) => ['code' => (string) $b->barangay_code, 'name' => $b->barangay])->values(),
            'hasErrors'   => $errors->any(),
            'old'         => [
                'id'                    => old('announcement_id'),
                'title'                 => old('title', ''),
                'subsidy_id'            => (string) old('subsidy_id', ''),
                'message'               => old('message', ''),
                'distribution_date'     => old('distribution_date', ''),
                'distribution_location' => old('distribution_location', ''),
                'barangay_codes'        => collect(old('barangay_codes', []))->map(fn ($c) => (string) $c)->values(),
            ],
        ];

        $all       = $announcements->getCollection();
        $drafts    = $all->where('status', 'draft')->count();
        $sentCount = $all->where('status', 'sent')->count();
        $upcoming  = $all->filter(fn ($a) => $a->distribution_date && $a->distribution_date->copy()->startOfDay()->gte(today()))
                         ->sortBy('distribution_date')->take(4);
    @endphp

    <div class="max-w-5xl mx-auto" x-data="announcementsPage(@js($config))">

        {{-- =====================================================
            BANNER
        ====================================================== --}}
        <div class="relative overflow-hidden rounded-2xl bg-[var(--brand)] text-white px-6 py-7 sm:px-8 sm:py-9 mb-6">
            <svg class="absolute -right-6 -bottom-8 h-48 w-48 text-white/10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09 4.1-.34 6.9-1.45 8.835-2.535v14.5C17.24 17.29 14.44 16.18 10.34 15.84z"/>
            </svg>
            <h1 class="text-2xl sm:text-3xl font-bold">Announcements</h1>
            <p class="mt-1 text-sm text-white/80 max-w-xl">Post subsidy distribution notices and send them to senior citizens by SMS.</p>
            <p class="mt-4 text-sm text-white/90">
                {{ $announcements->total() }} {{ \Illuminate\Support\Str::plural('announcement', $announcements->total()) }} total
                &nbsp;/&nbsp; {{ $drafts }} {{ \Illuminate\Support\Str::plural('draft', $drafts) }}
                &nbsp;/&nbsp; {{ $sentCount }} sent on this page
            </p>
        </div>

        {{-- Flash message: success --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition class="mb-4 flex items-center gap-2 p-4 rounded-xl bg-green-50 border border-green-100 text-green-700 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                <span class="flex-1">{{ session('success') }}</span>
                <button type="button" x-on:click="show = false" class="text-green-600 hover:text-green-800" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        {{-- Flash message: error (shows the real reason an SMS failed) --}}
        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition class="mb-4 flex items-start gap-2 p-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                <span class="flex-1 break-words">{{ session('error') }}</span>
                <button type="button" x-on:click="show = false" class="text-red-500 hover:text-red-700" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)] items-start">

            {{-- =====================================================
                SIDEBAR: filters + upcoming
            ====================================================== --}}
            <aside class="space-y-4 lg:sticky lg:top-6">
                <div class="rounded-2xl bg-white ring-1 ring-gray-200 p-4">
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">Show</h2>
                    <div class="flex flex-row flex-wrap lg:flex-col gap-1">
                        @foreach (['all' => 'All announcements', 'draft' => 'Drafts', 'sending' => 'Sending', 'sent' => 'Sent', 'failed' => 'Failed'] as $key => $label)
                            <button type="button" x-on:click="filter = '{{ $key }}'"
                                    x-bind:class="filter === '{{ $key }}' ? 'bg-[var(--brand-tint)] text-[var(--brand)] font-semibold' : 'text-gray-600 hover:bg-gray-50'"
                                    class="flex items-center justify-between rounded-lg px-3 py-2 text-sm text-left transition">
                                <span>{{ $label }}</span>
                                <span class="text-xs tabular-nums text-gray-400" x-text="countFor('{{ $key }}')"></span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl bg-white ring-1 ring-gray-200 p-4">
                    <h2 class="text-sm font-semibold text-gray-900">Upcoming distributions</h2>
                    @forelse ($upcoming as $u)
                        <button type="button" x-on:click="openView({{ $u->id }})" class="mt-3 block w-full text-left group">
                            <p class="text-xs text-gray-500">{{ $u->distribution_date->format('M d, Y') }}</p>
                            <p class="text-sm font-medium text-gray-800 group-hover:text-[var(--brand)] truncate">{{ $u->title }}</p>
                        </button>
                    @empty
                        <p class="mt-2 text-xs text-gray-400">Nothing scheduled. Distribution dates you set will show up here.</p>
                    @endforelse
                </div>
            </aside>

            {{-- =====================================================
                STREAM
            ====================================================== --}}
            <section class="space-y-4 min-w-0">

                {{-- Composer bar --}}
                <button type="button" x-on:click="openCreate()"
                        class="w-full flex items-center gap-3 rounded-2xl bg-white ring-1 ring-gray-200 hover:shadow-md px-4 py-4 text-left transition">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--brand)] text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    </span>
                    <span class="text-sm text-gray-500">Announce something to senior citizens…</span>
                </button>

                {{-- Posts --}}
                @foreach ($announcements as $announcement)
                    @php
                        $status   = $announcement->status;
                        $editable = in_array($status, ['draft', 'failed']);
                        $pct      = $announcement->recipients_count > 0
                                    ? min(100, round(($announcement->sent_count / $announcement->recipients_count) * 100))
                                    : 0;
                    @endphp

                    <article x-show="filter === 'all' || filter === '{{ $status }}'" x-transition.opacity
                             class="rounded-2xl bg-white ring-1 ring-gray-200 hover:shadow-md transition">

                        {{-- Post header --}}
                        <div class="flex items-start gap-3 px-5 pt-5">
                            <span @class([
                                'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                                'bg-gray-100 text-gray-500' => $status === 'draft',
                                'bg-yellow-100 text-yellow-600' => $status === 'sending',
                                'bg-green-100 text-green-700' => $status === 'sent',
                                'bg-red-100 text-red-600' => $status === 'failed',
                            ])>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535" /></svg>
                            </span>

                            <button type="button" x-on:click="openView({{ $announcement->id }})" class="flex-1 min-w-0 text-left">
                                <h3 class="text-base font-semibold text-gray-900 truncate hover:text-[var(--brand)] transition">{{ $announcement->title }}</h3>
                                <p class="text-xs text-gray-500">
                                    Posted {{ optional($announcement->created_at)->diffForHumans() }}
                                    @if ($announcement->subsidy) &nbsp;in {{ $announcement->subsidy->name }} @endif
                                </p>
                            </button>

                            <span @class([
                                'hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0',
                                'bg-gray-100 text-gray-600' => $status === 'draft',
                                'bg-yellow-100 text-yellow-700' => $status === 'sending',
                                'bg-green-100 text-green-700' => $status === 'sent',
                                'bg-red-100 text-red-700' => $status === 'failed',
                            ])>{{ ucfirst($status) }}</span>

                            {{-- Kebab menu --}}
                            <div class="relative shrink-0" x-data="{ open: false }" x-on:keydown.escape.window="open = false">
                                <button type="button" x-on:click="open = !open"
                                        class="rounded-full p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition focus:outline-none focus:ring-2 focus:ring-[var(--brand-ring)]"
                                        aria-label="Options for {{ $announcement->title }}" aria-haspopup="true" x-bind:aria-expanded="open">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                                </button>
                                <div x-show="open" x-cloak x-on:click.outside="open = false" x-transition.origin.top.right
                                     class="absolute right-0 z-20 mt-1 w-44 rounded-xl bg-white py-1 shadow-lg ring-1 ring-black/5" style="display:none;" role="menu">
                                    <button type="button" role="menuitem" x-on:click="open = false; openView({{ $announcement->id }})"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 010-.64C3.42 7.51 7.36 4.5 12 4.5c4.64 0 8.57 3.01 9.96 7.18.07.21.07.43 0 .64C20.58 16.49 16.64 19.5 12 19.5c-4.64 0-8.57-3.01-9.96-7.18z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        View
                                    </button>
                                    @if ($editable)
                                        <button type="button" role="menuitem" x-on:click="open = false; openEdit({{ $announcement->id }})"
                                                class="flex w-full items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                            Edit
                                        </button>
                                    @else
                                        <p class="px-3 py-2 text-xs text-gray-400" role="note">Sent messages can't be edited.</p>
                                    @endif
                                    <div class="my-1 border-t border-gray-100"></div>
                                    <button type="button" role="menuitem" x-on:click="open = false; openDelete({{ $announcement->id }})"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Message preview --}}
                        <button type="button" x-on:click="openView({{ $announcement->id }})" class="block w-full text-left px-5 pt-3">
                            <p class="text-sm text-gray-700 leading-relaxed line-clamp-3 whitespace-pre-line">{{ $announcement->message }}</p>
                        </button>

                        {{-- Details --}}
                        <div class="flex flex-wrap gap-x-5 gap-y-2 px-5 pt-3 text-xs text-gray-500">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                {{ optional($announcement->distribution_date)->format('M d, Y') ?? 'No date set' }}
                            </span>
                            @if ($announcement->distribution_location)
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                    {{ $announcement->distribution_location }}
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                @if ($announcement->barangays->isEmpty())
                                    All barangays
                                @else
                                    {{ $announcement->barangays->take(2)->pluck('barangay_name')->join(', ') }}
                                    @if ($announcement->barangays->count() > 2) +{{ $announcement->barangays->count() - 2 }} more @endif
                                @endif
                            </span>
                        </div>

                        {{-- Footer: delivery progress + actions --}}
                        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-gray-100 px-5 py-3">
                            <div class="flex-1 min-w-[10rem]">
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Delivered</span>
                                    <span class="tabular-nums">{{ $announcement->sent_count }} / {{ $announcement->recipients_count }}</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                    <div @class([
                                            'h-full rounded-full',
                                            'bg-[var(--brand)]' => $status !== 'failed',
                                            'bg-red-400' => $status === 'failed',
                                         ]) style="width: {{ $pct }}%"></div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" x-on:click="openView({{ $announcement->id }})"
                                        class="rounded-lg px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 transition">View</button>
                                @if ($editable)
                                    <button type="button" x-on:click="openEdit({{ $announcement->id }})"
                                            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 transition">Edit</button>
                                @endif

                                {{-- Drafts can be sent; failed announcements can be retried --}}
                                @if (in_array($status, ['draft', 'failed']))
                                    <form action="{{ route('announcements.send', $announcement) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                                class="rounded-lg px-3 py-1.5 text-xs font-semibold text-[var(--brand)] border border-[var(--brand)]/30 bg-[var(--brand-tint)] hover:bg-[var(--brand)] hover:text-white transition"
                                                onclick="return confirm('Send SMS to the selected recipients now?')">
                                            {{ $status === 'failed' ? 'Retry' : 'Send now' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach

                {{-- Empty state: nothing exists --}}
                @if ($announcements->isEmpty())
                    <div class="rounded-2xl bg-white ring-1 ring-gray-200 px-6 py-16 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[var(--brand-tint)] text-[var(--brand)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8-1.19 0-2.326-.203-3.362-.57L3 21l1.657-4.435A7.933 7.933 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-gray-800">No announcements yet</p>
                        <p class="mt-1 text-xs text-gray-500">Post your first distribution notice to reach senior citizens by SMS.</p>
                        <button type="button" x-on:click="openCreate()" class="mt-4 rounded-lg bg-[var(--brand)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90 transition">New announcement</button>
                    </div>
                @endif

                {{-- Empty state: filter matches nothing --}}
                @if ($announcements->isNotEmpty())
                    <div x-show="filter !== 'all' && countFor(filter) === 0" x-cloak style="display:none;"
                         class="rounded-2xl bg-white ring-1 ring-gray-200 px-6 py-12 text-center">
                        <p class="text-sm font-medium text-gray-700">No <span x-text="filter"></span> announcements on this page.</p>
                        <button type="button" x-on:click="filter = 'all'" class="mt-2 text-xs font-semibold text-[var(--brand)] hover:underline">Show all</button>
                    </div>
                @endif

                <div>{{ $announcements->links() }}</div>
            </section>
        </div>


        {{-- =====================================================
            VIEW MODAL
        ====================================================== --}}
        <div x-show="viewItem" x-cloak style="display:none;" x-on:keydown.escape.window="viewItem = null"
             class="fixed inset-0 z-50 flex items-center justify-center px-4" role="dialog" aria-modal="true">
            <div x-show="viewItem" x-transition.opacity x-on:click="viewItem = null" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div x-show="viewItem" x-transition.scale.95 x-on:click.stop
                 class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-xl ring-1 ring-black/5">
                <template x-if="viewItem">
                    <div>
                        <div class="flex items-start justify-between gap-4 px-6 py-4 border-b border-gray-100">
                            <div class="min-w-0">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" x-bind:class="badge(viewItem.status)" x-text="cap(viewItem.status)"></span>
                                <h3 class="mt-2 text-lg font-semibold text-gray-900 leading-snug" x-text="viewItem.title"></h3>
                                <p class="text-xs text-gray-500 mt-0.5">Posted <span x-text="viewItem.posted"></span> in <span x-text="viewItem.subsidy"></span></p>
                            </div>
                            <button type="button" x-on:click="viewItem = null" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-1.5 transition shrink-0" aria-label="Close">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="px-6 py-5 space-y-5">
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-1">SMS message</p>
                                <p class="rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-800 leading-relaxed whitespace-pre-line" x-text="viewItem.message"></p>
                            </div>

                            <dl class="grid grid-cols-2 gap-4 text-sm">
                                <div><dt class="text-xs text-gray-500">Distribution date</dt><dd class="mt-0.5 font-medium text-gray-900" x-text="viewItem.date_label"></dd></div>
                                <div><dt class="text-xs text-gray-500">Venue</dt><dd class="mt-0.5 font-medium text-gray-900" x-text="viewItem.venue || '—'"></dd></div>
                            </dl>

                            <div>
                                <p class="text-xs text-gray-500 mb-1.5">Target barangay(s)</p>
                                <template x-if="viewItem.barangay_names.length === 0">
                                    <p class="text-sm text-gray-600">All barangays</p>
                                </template>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="name in viewItem.barangay_names" x-bind:key="name">
                                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-600" x-text="name"></span>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Delivered</span>
                                    <span class="tabular-nums" x-text="viewItem.sent + ' / ' + viewItem.recipients"></span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded-full" x-bind:class="viewItem.status === 'failed' ? 'bg-red-400' : 'bg-[var(--brand)]'"
                                         x-bind:style="'width:' + (viewItem.recipients > 0 ? Math.min(100, viewItem.sent / viewItem.recipients * 100) : 0) + '%'"></div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 border-t border-gray-100 px-6 py-4">
                            <button type="button" x-on:click="openDelete(viewItem.id)" class="rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 transition">Delete</button>
                            <div class="flex items-center gap-2">
                                <button type="button" x-on:click="viewItem = null" class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">Close</button>
                                <template x-if="viewItem.status === 'draft' || viewItem.status === 'failed'">
                                    <button type="button" x-on:click="openEdit(viewItem.id)" class="rounded-lg bg-[var(--brand)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90 transition">Edit</button>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>


        {{-- =====================================================
            CREATE / EDIT MODAL
        ====================================================== --}}
        <div x-show="modalOpen" x-cloak style="display:none;" x-on:keydown.escape.window="if (!sending) modalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center px-4" role="dialog" aria-modal="true">
            <div x-show="modalOpen" x-transition.opacity x-on:click="if (!sending) modalOpen = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div x-show="modalOpen" x-transition.scale.95 x-on:click.stop
                 class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-xl ring-1 ring-black/5">

                <div class="flex items-start justify-between gap-4 px-6 py-4 border-b border-gray-100 sticky top-0 bg-white rounded-t-2xl z-10">
                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[var(--brand-tint)] text-[var(--brand)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535" /></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 leading-tight" x-text="mode === 'edit' ? 'Edit announcement' : 'New announcement'"></h3>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="mode === 'edit' ? 'Update the details, then save or send.' : 'Save it as a draft, or send it out right away.'"></p>
                        </div>
                    </div>
                    <button type="button" x-on:click="modalOpen = false" x-bind:disabled="sending" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-1.5 transition shrink-0" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if ($errors->any())
                    <div class="mx-6 mt-4 flex items-start gap-2.5 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        <div>
                            <p class="text-sm font-medium text-red-800">Please fix the following:</p>
                            <ul class="mt-1 text-xs text-red-700 list-disc list-inside space-y-0.5">
                                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{--
                    `sending` is set in a setTimeout so the browser serializes the form
                    (including the clicked submit button's name/value, i.e. send_now=1)
                    BEFORE Alpine disables the buttons. Otherwise "Save & send now" would be
                    submitted without send_now and silently saved as a draft.
                --}}
                <form x-bind:action="formAction" method="POST" class="p-6 space-y-6 text-left"
                      x-on:submit="setTimeout(() => sending = true, 0)">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <div>
                            <input type="hidden" name="_method" value="PUT">
                            <input type="hidden" name="announcement_id" x-bind:value="editId">
                        </div>
                    </template>

                    <div class="space-y-4">
                        <div>
                            <label for="f-title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                            <input type="text" id="f-title" name="title" x-model="form.title" placeholder="e.g. Free Flu Vaccination Drive"
                                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-[var(--brand)] focus:ring-2 focus:ring-[var(--brand-ring)] @error('title') border-red-400 @enderror">
                            @error('title') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="f-subsidy" class="block text-sm font-medium text-gray-700 mb-1">Subsidy type</label>
                            <select id="f-subsidy" name="subsidy_id" x-model="form.subsidy_id"
                                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-[var(--brand)] focus:ring-2 focus:ring-[var(--brand-ring)] @error('subsidy_id') border-red-400 @enderror">
                                <option value="">Select a subsidy…</option>
                                @foreach ($subsidies as $subsidy)
                                    <option value="{{ $subsidy->id }}">{{ $subsidy->name }}</option>
                                @endforeach
                            </select>
                            @error('subsidy_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                            @if ($subsidies->isEmpty())
                                <p class="text-xs text-amber-600 mt-1">No subsidies created yet. Create one first.</p>
                            @endif
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="f-message" class="block text-sm font-medium text-gray-700">Message <span class="text-gray-400 font-normal">(sent via SMS)</span></label>
                                <span class="text-xs text-gray-400 tabular-nums" x-text="form.message.length + ' / 300'"></span>
                            </div>
                            <textarea id="f-message" name="message" rows="4" maxlength="300" x-model="form.message"
                                      placeholder="Keep it short and clear — this will be delivered as a text message."
                                      class="w-full rounded-lg border-gray-300 shadow-sm text-sm resize-none focus:border-[var(--brand)] focus:ring-2 focus:ring-[var(--brand-ring)] @error('message') border-red-400 @enderror"></textarea>
                            <div class="mt-1.5 h-1 w-full rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                     x-bind:class="pct >= 100 ? 'bg-red-500' : (pct >= 85 ? 'bg-amber-500' : 'bg-[var(--brand)]')"
                                     x-bind:style="'width:' + pct + '%'"></div>
                            </div>
                            @error('message') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                        <div>
                            <label for="f-date" class="block text-sm font-medium text-gray-700 mb-1">Distribution date</label>
                            <input type="date" id="f-date" name="distribution_date" x-model="form.distribution_date"
                                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-[var(--brand)] focus:ring-2 focus:ring-[var(--brand-ring)] @error('distribution_date') border-red-400 @enderror">
                            @error('distribution_date') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="f-venue" class="block text-sm font-medium text-gray-700 mb-1">Venue</label>
                            <input type="text" id="f-venue" name="distribution_location" x-model="form.distribution_location" placeholder="e.g. Barangay Hall"
                                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-[var(--brand)] focus:ring-2 focus:ring-[var(--brand-ring)] @error('distribution_location') border-red-400 @enderror">
                            @error('distribution_location') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Barangays --}}
                    <div class="pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="block text-sm font-medium text-gray-700">Target barangay(s)</span>
                            <label class="inline-flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer select-none">
                                <input type="checkbox" x-bind:checked="allVisibleSelected" x-on:change="toggleAll()"
                                       class="rounded border-gray-300 text-[var(--brand)] focus:ring-[var(--brand)]">
                                Select all
                            </label>
                        </div>

                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <template x-if="barangays.length">
                                <div class="relative border-b border-gray-100 bg-gray-50">
                                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                                    <input type="text" x-model="search" placeholder="Search barangay…"
                                           class="w-full border-0 bg-transparent pl-8 pr-3 py-2 text-sm placeholder:text-gray-400 focus:ring-0">
                                </div>
                            </template>

                            <div class="max-h-36 overflow-y-auto divide-y divide-gray-100">
                                <template x-for="b in visibleBarangays" x-bind:key="b.code">
                                    <label class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="barangay_codes[]" x-bind:value="b.code" x-model="form.barangay_codes"
                                               class="rounded border-gray-300 text-[var(--brand)] focus:ring-[var(--brand)]">
                                        <span x-text="b.name"></span>
                                    </label>
                                </template>
                                <p x-show="barangays.length === 0" class="px-3 py-4 text-xs text-gray-400 text-center">No barangays found in senior citizen records yet.</p>
                                <p x-show="barangays.length > 0 && visibleBarangays.length === 0" x-cloak class="px-3 py-4 text-xs text-gray-400 text-center">No barangay matches your search.</p>
                            </div>
                        </div>
                        @error('barangay_codes') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-400 mt-1.5">
                            Only active senior citizens registered in the selected barangay(s) will be enlisted and notified.
                            Leave all unchecked to include every active senior citizen.
                        </p>
                    </div>

                    <div x-show="recipients !== null" x-cloak class="flex items-start gap-2.5 rounded-lg bg-[var(--brand-tint)] px-4 py-3">
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-[var(--brand)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="text-xs text-gray-600 leading-relaxed pt-0.5">
                            This will be sent to <span class="font-semibold text-gray-800" x-text="recipientsLoading ? '…' : recipients"></span>
                            active senior citizen(s) with a contact number on file.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 -mx-6 px-6 pt-4">
                        <button type="button" x-on:click="modalOpen = false" x-bind:disabled="sending"
                                class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition disabled:opacity-40 disabled:cursor-not-allowed">Cancel</button>
                        <button type="submit" name="send_now" value="0" x-bind:disabled="sending"
                                class="px-4 py-2 rounded-lg text-sm font-semibold border border-gray-300 text-gray-700 hover:bg-gray-50 transition disabled:opacity-40 disabled:cursor-not-allowed"
                                x-text="mode === 'edit' ? 'Save changes' : 'Save as draft'"></button>
                        <button type="submit" name="send_now" value="1" x-bind:disabled="sending"
                                x-on:click="if (!confirm('Send SMS to the selected recipients now?')) $event.preventDefault()"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-[var(--brand)] hover:opacity-90 active:opacity-80 transition shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg x-show="sending" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="sending ? 'Sending…' : 'Save & send now'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>


        {{-- =====================================================
            DELETE CONFIRMATION MODAL
        ====================================================== --}}
        <div x-show="deleteItem" x-cloak style="display:none;" x-on:keydown.escape.window="deleteItem = null"
             class="fixed inset-0 z-[60] flex items-center justify-center px-4" role="alertdialog" aria-modal="true">
            <div x-show="deleteItem" x-transition.opacity x-on:click="deleteItem = null" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div x-show="deleteItem" x-transition.scale.95 x-on:click.stop class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl ring-1 ring-black/5">
                <template x-if="deleteItem">
                    <form x-bind:action="deleteItem.destroy_url" method="POST" x-on:submit="setTimeout(() => deleting = true, 0)">
                        @csrf
                        @method('DELETE')
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-red-100 text-red-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-gray-900">Delete this announcement?</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            “<span class="font-medium" x-text="deleteItem.title"></span>” will be removed permanently.
                            <span x-show="deleteItem.status === 'sent'" x-cloak>Senior citizens who already received the SMS won't be affected.</span>
                        </p>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" x-on:click="deleteItem = null" x-bind:disabled="deleting"
                                    class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 transition disabled:opacity-40">Cancel</button>
                            <button type="submit" x-bind:disabled="deleting"
                                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition disabled:opacity-60"
                                    x-text="deleting ? 'Deleting…' : 'Delete announcement'"></button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

    </div>

    <script>
        function announcementsPage(cfg) {
            const blank = () => ({
                title: '', subsidy_id: '', message: '',
                distribution_date: '', distribution_location: '', barangay_codes: [],
            });

            const restoring = cfg.hasErrors;

            return {
                items: cfg.items,
                barangays: cfg.barangays,
                recipients: cfg.recipients,
                recipientsLoading: false,

                filter: 'all',
                search: '',
                sending: false,
                deleting: false,

                modalOpen: restoring,
                mode: restoring && cfg.old.id ? 'edit' : 'create',
                editId: restoring && cfg.old.id ? cfg.old.id : null,
                form: restoring ? {
                    title: cfg.old.title, subsidy_id: cfg.old.subsidy_id, message: cfg.old.message,
                    distribution_date: cfg.old.distribution_date, distribution_location: cfg.old.distribution_location,
                    barangay_codes: [...cfg.old.barangay_codes],
                } : blank(),

                viewItem: null,
                deleteItem: null,
                _timer: null,

                init() {
                    this.$watch('form.barangay_codes', () => this.refreshRecipients());
                },

                // ---- derived ----
                get formAction() {
                    return this.mode === 'edit' && this.items[this.editId]
                        ? this.items[this.editId].update_url
                        : cfg.storeUrl;
                },
                get pct() { return Math.min(100, (this.form.message.length / 300) * 100); },
                get visibleBarangays() {
                    const q = this.search.trim().toLowerCase();
                    return this.barangays.filter(b => b.name.toLowerCase().includes(q));
                },
                get allVisibleSelected() {
                    return this.visibleBarangays.length > 0
                        && this.visibleBarangays.every(b => this.form.barangay_codes.includes(b.code));
                },

                // ---- actions ----
                openCreate() {
                    this.mode = 'create'; this.editId = null; this.form = blank();
                    this.search = ''; this.sending = false; this.viewItem = null; this.modalOpen = true;
                    this.refreshRecipients();
                },
                openEdit(id) {
                    const a = this.items[id]; if (!a) return;
                    this.mode = 'edit'; this.editId = id;
                    this.form = {
                        title: a.title, subsidy_id: a.subsidy_id, message: a.message || '',
                        distribution_date: a.distribution_date || '', distribution_location: a.venue || '',
                        barangay_codes: [...a.barangay_codes],
                    };
                    this.search = ''; this.sending = false; this.viewItem = null; this.modalOpen = true;
                    this.refreshRecipients();
                },
                openView(id) { this.viewItem = this.items[id] || null; },
                openDelete(id) { this.deleting = false; this.viewItem = null; this.deleteItem = this.items[id] || null; },

                toggleAll() {
                    const codes = this.visibleBarangays.map(b => b.code);
                    this.form.barangay_codes = this.allVisibleSelected
                        ? this.form.barangay_codes.filter(c => !codes.includes(c))
                        : [...new Set([...this.form.barangay_codes, ...codes])];
                },

                countFor(key) {
                    const list = Object.values(this.items);
                    return key === 'all' ? list.length : list.filter(a => a.status === key).length;
                },

                badge(status) {
                    return {
                        draft: 'bg-gray-100 text-gray-600',
                        sending: 'bg-yellow-100 text-yellow-700',
                        sent: 'bg-green-100 text-green-700',
                        failed: 'bg-red-100 text-red-700',
                    }[status] || 'bg-gray-100 text-gray-600';
                },
                cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; },

                refreshRecipients() {
                    if (this.recipients === null) return;
                    clearTimeout(this._timer);
                    this._timer = setTimeout(() => {
                        const params = new URLSearchParams();
                        this.form.barangay_codes.forEach(c => params.append('barangay_codes[]', c));
                        this.recipientsLoading = true;
                        fetch(`${cfg.countUrl}?${params.toString()}`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        })
                            .then(r => r.json())
                            .then(d => { this.recipients = d.count; })
                            .catch(() => {})
                            .finally(() => { this.recipientsLoading = false; });
                    }, 250);
                },
            };
        }
    </script>

</x-app-layout>