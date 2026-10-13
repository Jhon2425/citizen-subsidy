<x-app-layout>

    {{-- =========================================================
        DASHBOARD  (server-rendered cards + Chart.js charts)
        No React / Vite entry needed.
    ========================================================== --}}

    @php
        $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        $dashboardPayload = [
            'months' => $monthLabels,
            'monthlyApplications' => collect(range(1, 12))
                ->map(fn ($m) => (int) (optional($monthlyApplications->firstWhere('month', $m))->total ?? 0))
                ->values(),
            'monthlySubsidies' => collect(range(1, 12))
                ->map(fn ($m) => (float) (optional($monthlySubsidies->firstWhere('month', $m))->total ?? 0))
                ->values(),
            'subsidyBreakdown' => $subsidyBreakdown
                ->map(fn ($row) => ['name' => (string) $row->name, 'total' => (float) $row->total_amount])
                ->filter(fn ($row) => $row['total'] > 0)
                ->values(),
            'status' => [
                ['name' => 'Pending',          'value' => (int) $pendingApplications],
                ['name' => 'For verification', 'value' => (int) $verificationApplications],
                ['name' => 'Approved',         'value' => (int) $approvedApplications],
                ['name' => 'Rejected',         'value' => (int) $rejectedApplications],
            ],
        ];

        $cards = [
            ['label' => 'Total Senior Citizens',      'value' => number_format($totalSeniorCitizens),
             'hint' => 'Registered in the system',    'tone' => 'bg-indigo-50 text-indigo-600'],
            ['label' => 'Active Beneficiaries',       'value' => number_format($activeBeneficiaries),
             'hint' => number_format($approvedBeneficiaries) . ' approved', 'tone' => 'bg-emerald-50 text-emerald-600'],
            ['label' => 'Pending Applications',       'value' => number_format($pendingApplications),
             'hint' => 'Awaiting review',             'tone' => 'bg-amber-50 text-amber-600'],
            ['label' => 'Total Subsidy Distributed',  'value' => '₱' . number_format((float) $totalSubsidyDistributed, 2),
             'hint' => '₱' . number_format((float) $thisMonthSubsidy, 2) . ' this month (' . number_format($thisMonthReleases) . ' releases)',
             'tone' => 'bg-sky-50 text-sky-600'],
        ];
    @endphp

    <div class="max-w-7xl mx-auto">

        {{-- PAGE HEADING --}}
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-950">Dashboard</h1>
                <p class="mt-1 text-sm text-gray-500">Overview of the Senior Citizen Subsidy Management System.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-lg bg-white px-3.5 py-2 text-sm font-medium text-gray-600 ring-1 ring-gray-950/5 shadow-sm">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                {{ now()->format('F d, Y') }}
            </div>
        </div>

        {{-- STAT CARDS --}}
        <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $card)
                <div class="rounded-xl bg-white p-6 ring-1 ring-gray-950/5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <p class="text-sm font-medium text-gray-500">{{ $card['label'] }}</p>
                        <span class="h-9 w-9 rounded-lg {{ $card['tone'] }}"></span>
                    </div>
                    <p class="mt-3 text-2xl font-bold tracking-tight text-gray-950">{{ $card['value'] }}</p>
                    <p class="mt-2 text-xs text-gray-400">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- CHARTS --}}
        <div class="mb-8 grid grid-cols-1 gap-5 xl:grid-cols-2">
            @foreach ([
                ['id' => 'chartApplications', 'title' => 'Applications per Month',     'sub' => 'Applications received this year'],
                ['id' => 'chartSubsidies',    'title' => 'Subsidy Released per Month', 'sub' => 'Amount released this year'],
                ['id' => 'chartBreakdown',    'title' => 'Subsidy Breakdown',          'sub' => 'Total released per program'],
                ['id' => 'chartStatus',       'title' => 'Application Status',         'sub' => 'All applications by status'],
            ] as $chart)
                <div class="rounded-xl bg-white p-6 ring-1 ring-gray-950/5 shadow-sm">
                    <h3 class="text-base font-semibold text-gray-950">{{ $chart['title'] }}</h3>
                    <p class="mb-4 text-sm text-gray-500">{{ $chart['sub'] }}</p>
                    <div class="relative h-72" id="wrap-{{ $chart['id'] }}">
                        <canvas id="{{ $chart['id'] }}"></canvas>
                    </div>
                </div>
            @endforeach
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                var data = @json($dashboardPayload);
                var COLORS = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6', '#ec4899'];
                var peso = function (n) {
                    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(n || 0);
                };

                function empty(id, text) {
                    var wrap = document.getElementById('wrap-' + id);
                    wrap.innerHTML = '<div class="flex h-full items-center justify-center text-sm text-gray-400">' + text + '</div>';
                }

                if (typeof Chart === 'undefined') {
                    ['chartApplications', 'chartSubsidies', 'chartBreakdown', 'chartStatus'].forEach(function (id) {
                        empty(id, 'Chart library failed to load (check internet connection)');
                    });
                    return;
                }

                Chart.defaults.font.family = 'inherit';
                var sum = function (arr) { return arr.reduce(function (a, b) { return a + b; }, 0); };

                // Applications per month
                if (sum(data.monthlyApplications) > 0) {
                    new Chart(document.getElementById('chartApplications'), {
                        type: 'bar',
                        data: { labels: data.months, datasets: [{ label: 'Applications', data: data.monthlyApplications, backgroundColor: '#4f46e5', borderRadius: 4 }] },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
                    });
                } else { empty('chartApplications', 'No applications recorded this year'); }

                // Subsidy per month
                if (sum(data.monthlySubsidies) > 0) {
                    new Chart(document.getElementById('chartSubsidies'), {
                        type: 'line',
                        data: { labels: data.months, datasets: [{ label: 'Released', data: data.monthlySubsidies, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.15)', fill: true, tension: 0.3 }] },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return peso(c.parsed.y); } } } }, scales: { y: { beginAtZero: true } } }
                    });
                } else { empty('chartSubsidies', 'No subsidy releases recorded this year'); }

                // Breakdown
                if (data.subsidyBreakdown.length > 0) {
                    new Chart(document.getElementById('chartBreakdown'), {
                        type: 'doughnut',
                        data: { labels: data.subsidyBreakdown.map(function (r) { return r.name; }), datasets: [{ data: data.subsidyBreakdown.map(function (r) { return r.total; }), backgroundColor: COLORS }] },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: function (c) { return c.label + ': ' + peso(c.parsed); } } } } }
                    });
                } else { empty('chartBreakdown', 'No subsidy data to break down'); }

                // Status
                var statuses = data.status.filter(function (r) { return r.value > 0; });
                if (statuses.length > 0) {
                    new Chart(document.getElementById('chartStatus'), {
                        type: 'doughnut',
                        data: { labels: statuses.map(function (r) { return r.name; }), datasets: [{ data: statuses.map(function (r) { return r.value; }), backgroundColor: COLORS }] },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                    });
                } else { empty('chartStatus', 'No applications yet'); }
            })();
        </script>

        {{-- RECENT ANNOUNCEMENTS --}}
        <div class="rounded-xl bg-white ring-1 ring-gray-950/5 shadow-sm overflow-hidden">

            <div class="p-6 border-b border-gray-100 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-gray-950">Recent Announcements</h2>
                        <p class="text-sm text-gray-500">Subsidy distribution notices sent to senior citizens</p>
                    </div>
                </div>

                <a href="{{ route('announcements.index') }}" class="shrink-0 inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-500 whitespace-nowrap">
                    View all
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                    </svg>
                </a>
            </div>

            <div class="divide-y divide-gray-100">

                @forelse ($recentAnnouncements as $announcement)

                    @php
                        $statusStyles = [
                            'draft'   => ['badge' => 'bg-gray-50 text-gray-600 ring-gray-500/10',    'dot' => 'bg-gray-400',    'icon' => 'bg-gray-50 text-gray-400'],
                            'sending' => ['badge' => 'bg-amber-50 text-amber-700 ring-amber-600/10',  'dot' => 'bg-amber-500',   'icon' => 'bg-amber-50 text-amber-500'],
                            'sent'    => ['badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10', 'dot' => 'bg-emerald-500', 'icon' => 'bg-emerald-50 text-emerald-500'],
                            'failed'  => ['badge' => 'bg-rose-50 text-rose-700 ring-rose-600/10',     'dot' => 'bg-rose-500',    'icon' => 'bg-rose-50 text-rose-500'],
                        ];
                        $style = $statusStyles[$announcement->status] ?? $statusStyles['draft'];
                        $notifiedPct = $announcement->recipients_count > 0
                            ? min(100, round(($announcement->sent_count / $announcement->recipients_count) * 100))
                            : 0;
                    @endphp

                    <div class="p-6 flex items-start justify-between gap-4 hover:bg-gray-50/60 transition-colors">

                        <div class="min-w-0 flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $style['icon'] }}">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-950 truncate">{{ $announcement->title }}</p>
                                <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $announcement->message }}</p>
                                <p class="flex items-center gap-1.5 text-xs text-gray-400 mt-2">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A9.001 9.001 0 003 12a9 9 0 109-9.036V3z" />
                                    </svg>
                                    @if ($announcement->distribution_date)
                                        Distribution: {{ $announcement->distribution_date->format('M d, Y') }}
                                        @if ($announcement->distribution_location)
                                            &middot; {{ $announcement->distribution_location }}
                                        @endif
                                    @else
                                        No distribution date set
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="text-right shrink-0 w-36">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $style['badge'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $style['dot'] }}"></span>
                                {{ ucfirst($announcement->status) }}
                            </span>

                            <p class="text-xs text-gray-500 mt-2.5">
                                <span class="font-medium text-gray-700">{{ $announcement->sent_count }}</span> / {{ $announcement->recipients_count }} notified
                            </p>
                            <div class="mt-1.5 h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $style['dot'] }}" style="width: {{ $notifiedPct }}%"></div>
                            </div>
                        </div>

                    </div>

                @empty

                    <div class="p-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-50">
                            <svg class="h-6 w-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75L19.5 12l-2.25 2.25m-10.5 0L4.5 12l2.25-2.25M14.25 6l-4.5 12" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm font-medium text-gray-900">No announcements yet</p>
                        <p class="mt-1 text-sm text-gray-400">Distribution notices you send will show up here.</p>
                    </div>

                @endforelse

            </div>

        </div>

    </div>

</x-app-layout>