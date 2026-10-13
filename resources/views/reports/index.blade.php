<x-app-layout>

@php
    $field = 'block rounded-xl border-gray-300 text-sm shadow-sm focus:border-[oklch(45%_0.15_151.711)] focus:ring-[oklch(45%_0.15_151.711)] disabled:bg-gray-100 disabled:text-gray-400';

    // Expected from the controller:
    //   $year     int
    //   $years    Collection<int>
    //   $counts   ['pending','approved','rejected','total','deceased' => int]
    //   $monthly  Collection of ['label','pending','approved','rejected','total','deceased'] (12 rows, Jan–Dec)
    $total         = max($counts['total'], 0);
    $deceasedTotal = (int) ($counts['deceased'] ?? 0);
    $peak          = max(1, (int) $monthly->max('total'));
    $percent       = fn (int $n) => $total > 0 ? round($n / $total * 100) : 0;

    $cards = [
        ['key' => 'pending',  'label' => 'Pending',  'dot' => 'bg-yellow-500', 'pill' => 'bg-yellow-100 text-yellow-700', 'hint' => 'Waiting for review'],
        ['key' => 'approved', 'label' => 'Approved', 'dot' => 'bg-green-500',  'pill' => 'bg-green-100 text-green-700',   'hint' => 'Added to the master list'],
        ['key' => 'rejected', 'label' => 'Rejected', 'dot' => 'bg-red-500',    'pill' => 'bg-red-100 text-red-700',       'hint' => 'Did not qualify'],
    ];
@endphp

<div class="max-w-7xl mx-auto">

{{-- =====================================================
    BANNER
====================================================== --}}
<div class="relative mb-6 overflow-hidden rounded-2xl bg-[oklch(45%_0.15_151.711)] px-6 py-7 text-white sm:px-8 sm:py-9">
<svg class="pointer-events-none absolute -right-6 -bottom-10 size-48 text-white/10" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.5 4.5 0 100-9 4.5 4.5 0 000 9zm0 2c-4.14 0-7.5 2.24-7.5 5v1.5h15V19c0-2.76-3.36-5-7.5-5z"/></svg>
<div class="flex flex-wrap items-start justify-between gap-4">
<div>
<h1 class="text-2xl font-bold sm:text-3xl">Reports</h1>
<p class="mt-1 max-w-xl text-sm text-white/80">Application headcounts by status, applications added each month, and senior citizens marked deceased.</p>
<p class="mt-4 text-sm text-white/90">{{ number_format($counts['total']) }} {{ \Illuminate\Support\Str::plural('application', $counts['total']) }} in {{ $year }}</p>
</div>
<a href="{{ route('reports.export', ['year' => $year]) }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-[oklch(38%_0.15_151.711)] shadow-sm transition hover:bg-green-50">
<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
Export to Excel
</a>
</div>
</div>


@if (session('error'))
<div x-data="{ show: true }" x-show="show" x-transition class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4" role="alert">
<svg class="mt-0.5 size-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
<p class="flex-1 text-sm font-medium text-red-800">{{ session('error') }}</p>
<button type="button" x-on:click="show = false" class="text-red-600 hover:text-red-800" aria-label="Dismiss">
<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
</button>
</div>
@endif


{{-- =====================================================
    FILTERS
====================================================== --}}
<form method="GET" action="{{ route('reports.index') }}" class="mb-6 flex flex-wrap items-center gap-3">
<div class="w-full sm:w-40">
<label for="year" class="sr-only">Year</label>
<select name="year" id="year" class="{{ $field }} w-full" onchange="this.form.submit()">
@foreach ($years as $y)
<option value="{{ $y }}" @selected((int) $y === (int) $year)>{{ $y }}</option>
@endforeach
</select>
</div>
<noscript><button type="submit" class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white">Apply</button></noscript>
</form>


{{-- =====================================================
    HEADCOUNTS
====================================================== --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
<p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total applications</p>
<p class="mt-2 text-3xl font-bold tabular-nums text-gray-900">{{ number_format($counts['total']) }}</p>
<p class="mt-1 text-xs text-gray-400">All statuses, {{ $year }}</p>
</div>

@foreach ($cards as $card)
<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
<div class="flex items-center justify-between gap-2">
<p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $card['label'] }}</p>
<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $card['pill'] }}">
<span class="size-1.5 rounded-full {{ $card['dot'] }}"></span>{{ $percent($counts[$card['key']]) }}%
</span>
</div>
<p class="mt-2 text-3xl font-bold tabular-nums text-gray-900">{{ number_format($counts[$card['key']]) }}</p>
<p class="mt-1 text-xs text-gray-400">{{ $card['hint'] }}</p>
</div>
@endforeach

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
<div class="flex items-center gap-2">
<span class="size-2 rounded-full bg-gray-500"></span>
<p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Deceased</p>
</div>
<p class="mt-2 text-3xl font-bold tabular-nums text-gray-900">{{ number_format($deceasedTotal) }}</p>
<p class="mt-1 text-xs text-gray-400">Marked deceased in {{ $year }}</p>
</div>

</div>


{{-- =====================================================
    MONTHLY CHART
====================================================== --}}
<div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">

<div class="flex flex-wrap items-center justify-between gap-3">
<div>
<h2 class="text-base font-semibold text-gray-900">Applications added per month</h2>
<p class="text-sm text-gray-500">{{ $year }}, split by current status</p>
</div>
<div class="flex items-center gap-4 text-xs text-gray-600">
<span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-yellow-500"></span>Pending</span>
<span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-green-500"></span>Approved</span>
<span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-red-500"></span>Rejected</span>
</div>
</div>

<div class="mt-6 overflow-x-auto">
<div class="flex h-56 min-w-[36rem] items-end gap-2 border-b border-gray-200">
@foreach ($monthly as $row)
<div class="flex h-full flex-1 flex-col items-center justify-end gap-1"
     title="{{ $row['label'] }}: {{ $row['total'] }} total ({{ $row['pending'] }} pending, {{ $row['approved'] }} approved, {{ $row['rejected'] }} rejected)">
<span class="text-[11px] font-semibold tabular-nums text-gray-500">{{ $row['total'] ?: '' }}</span>
<div class="flex w-full max-w-10 flex-col-reverse overflow-hidden rounded-t-md" style="height: {{ round($row['total'] / $peak * 85) }}%">
@if ($row['approved'])<div class="bg-green-500" style="flex: {{ $row['approved'] }}"></div>@endif
@if ($row['pending'])<div class="bg-yellow-500" style="flex: {{ $row['pending'] }}"></div>@endif
@if ($row['rejected'])<div class="bg-red-500" style="flex: {{ $row['rejected'] }}"></div>@endif
</div>
</div>
@endforeach
</div>
<div class="mt-2 flex min-w-[36rem] gap-2">
@foreach ($monthly as $row)
<span class="flex-1 text-center text-[11px] font-medium text-gray-500">{{ \Illuminate\Support\Str::before($row['label'], ' ') }}</span>
@endforeach
</div>
</div>

</div>


{{-- =====================================================
    MONTHLY TABLE
====================================================== --}}
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

<div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
<div>
<h2 class="text-base font-semibold text-gray-900">Monthly breakdown</h2>
<p class="text-sm text-gray-500">The same figures that are included in the Excel export.</p>
</div>
<a href="{{ route('reports.export', ['year' => $year]) }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-300 px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
Export to Excel
</a>
</div>

<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-gray-200">

<thead class="bg-gray-50">
<tr>
<th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Month</th>
<th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Pending</th>
<th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Approved</th>
<th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Rejected</th>
<th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total added</th>
<th scope="col" class="border-l border-gray-200 px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Deceased</th>
</tr>
</thead>

<tbody class="divide-y divide-gray-100 bg-white">
@foreach ($monthly as $row)
<tr class="transition hover:bg-gray-50">
<td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900">{{ $row['label'] }}</td>
<td class="whitespace-nowrap px-6 py-4 text-right text-sm tabular-nums {{ $row['pending'] ? 'text-gray-900' : 'text-gray-300' }}">{{ number_format($row['pending']) }}</td>
<td class="whitespace-nowrap px-6 py-4 text-right text-sm tabular-nums {{ $row['approved'] ? 'text-gray-900' : 'text-gray-300' }}">{{ number_format($row['approved']) }}</td>
<td class="whitespace-nowrap px-6 py-4 text-right text-sm tabular-nums {{ $row['rejected'] ? 'text-gray-900' : 'text-gray-300' }}">{{ number_format($row['rejected']) }}</td>
<td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold tabular-nums {{ $row['total'] ? 'text-gray-900' : 'text-gray-300' }}">{{ number_format($row['total']) }}</td>
<td class="whitespace-nowrap border-l border-gray-100 px-6 py-4 text-right text-sm tabular-nums {{ ($row['deceased'] ?? 0) ? 'text-gray-900' : 'text-gray-300' }}">{{ number_format($row['deceased'] ?? 0) }}</td>
</tr>
@endforeach
</tbody>

<tfoot class="bg-gray-50">
<tr>
<th scope="row" class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Total {{ $year }}</th>
<td class="px-6 py-3 text-right text-sm font-semibold tabular-nums text-gray-900">{{ number_format($counts['pending']) }}</td>
<td class="px-6 py-3 text-right text-sm font-semibold tabular-nums text-gray-900">{{ number_format($counts['approved']) }}</td>
<td class="px-6 py-3 text-right text-sm font-semibold tabular-nums text-gray-900">{{ number_format($counts['rejected']) }}</td>
<td class="px-6 py-3 text-right text-sm font-bold tabular-nums text-gray-900">{{ number_format($counts['total']) }}</td>
<td class="border-l border-gray-200 px-6 py-3 text-right text-sm font-semibold tabular-nums text-gray-900">{{ number_format($deceasedTotal) }}</td>
</tr>
</tfoot>

</table>
</div>

</div>

</div>

</x-app-layout>