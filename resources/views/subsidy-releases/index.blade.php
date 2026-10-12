<x-app-layout>

@php
    $field = 'block rounded-xl border-gray-300 text-sm shadow-sm focus:border-[oklch(45%_0.15_151.711)] focus:ring-[oklch(45%_0.15_151.711)] disabled:bg-gray-100 disabled:text-gray-400';
    $hasFilters = request()->hasAny(['search', 'subsidy', 'date_from', 'date_to'])
        && collect($filters ?? [])->filter(fn ($v) => filled($v))->isNotEmpty();
    $releaseCount = method_exists($releases, 'total') ? $releases->total() : null;
@endphp

<div class="max-w-7xl mx-auto">

{{-- =====================================================
    BANNER
====================================================== --}}
<div class="relative mb-6 overflow-hidden rounded-2xl bg-[oklch(45%_0.15_151.711)] px-6 py-7 text-white sm:px-8 sm:py-9">
<svg class="pointer-events-none absolute -right-6 -bottom-10 size-48 text-white/10" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.5 4.5 0 100-9 4.5 4.5 0 000 9zm0 2c-4.14 0-7.5 2.24-7.5 5v1.5h15V19c0-2.76-3.36-5-7.5-5z"/></svg>
<div>
<h1 class="text-2xl font-bold sm:text-3xl">Subsidy Releases</h1>
<p class="mt-1 max-w-xl text-sm text-white/80">History of subsidies released to senior citizens.</p>
<p class="mt-4 text-sm text-white/90">
@if ($releaseCount !== null)
{{ $releaseCount }} {{ \Illuminate\Support\Str::plural('release', $releaseCount) }} {{ $hasFilters ? 'match your filters' : 'in total' }} &middot;
@endif
Total released in this view:
<span class="font-semibold tabular-nums">&#8369;{{ number_format($totalReleased, 2) }}</span>
</p>
</div>
</div>


@if (session('success'))
<div x-data="{ show: true }" x-show="show" x-transition class="mb-6 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 p-4" role="status">
<svg class="mt-0.5 size-5 shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
<p class="flex-1 text-sm font-medium text-green-800">{{ session('success') }}</p>
<button type="button" x-on:click="show = false" class="text-green-600 hover:text-green-800" aria-label="Dismiss">
<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
</button>
</div>
@endif

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
<form method="GET" action="{{ route('subsidy-releases.index') }}" class="mb-6 flex flex-wrap items-center gap-3">

<div class="w-full sm:w-52">
<label for="subsidy" class="sr-only">Subsidy</label>
<select name="subsidy" id="subsidy" class="{{ $field }} w-full" onchange="this.form.submit()">
<option value="">All Subsidies</option>
@foreach ($subsidies as $subsidy)
<option value="{{ $subsidy->id }}" @selected(($filters['subsidy'] ?? '') == $subsidy->id)>{{ $subsidy->name }}</option>
@endforeach
</select>
</div>

<div class="relative w-full sm:w-72">
<label for="search" class="sr-only">Search by name</label>
<svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
<input type="search" name="search" id="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by name" class="{{ $field }} w-full pl-9">
</div>

<div class="flex w-full items-center gap-2 sm:w-auto">
<label for="date_from" class="sr-only">From date</label>
<input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}" title="From date" class="{{ $field }} w-full sm:w-40">
<span class="text-sm text-gray-400">to</span>
<label for="date_to" class="sr-only">To date</label>
<input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}" title="To date" class="{{ $field }} w-full sm:w-40">
</div>

<button type="submit" class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">Search</button>

@if ($hasFilters)
<a href="{{ route('subsidy-releases.index') }}" class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">Clear</a>
@endif
</form>


{{-- =====================================================
    RELEASES TABLE
====================================================== --}}
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-gray-200">

<thead class="bg-gray-50">
<tr>
<th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">No.</th>
<th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Full Name</th>
<th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Subsidy</th>
<th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Amount</th>
<th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Release Date</th>
<th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
<th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Released By</th>
</tr>
</thead>

<tbody class="divide-y divide-gray-100 bg-white">

@forelse ($releases as $release)
@php
    $badge = match ($release->status) {
        'released'  => ['bg-green-100 text-green-700', 'bg-green-500'],
        'cancelled' => ['bg-red-100 text-red-700', 'bg-red-500'],
        default     => ['bg-yellow-100 text-yellow-700', 'bg-yellow-500'],
    };
    $senior = $release->seniorCitizen;
@endphp
<tr class="transition hover:bg-gray-50">

<td class="whitespace-nowrap px-6 py-4">
<p class="text-sm font-semibold tabular-nums text-gray-900">{{ $releases->firstItem() + $loop->index }}</p>
</td>

<td class="whitespace-nowrap px-6 py-4">
<p class="truncate text-sm font-semibold text-gray-900">{{ $senior?->list_name ?: ($senior ? trim($senior->first_name . ' ' . $senior->last_name) : '—') }}</p>
@if ($senior?->gender)
<p class="text-xs text-gray-500">{{ ucfirst($senior->gender) }}</p>
@endif
</td>

<td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ $release->subsidy?->name ?? '—' }}</td>

<td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold tabular-nums text-gray-900">&#8369;{{ number_format($release->amount, 2) }}</td>

<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $release->release_date->format('M d, Y') }}</td>

<td class="whitespace-nowrap px-6 py-4">
<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $badge[0] }}">
<span class="size-1.5 rounded-full {{ $badge[1] }}"></span>{{ ucfirst($release->status) }}
</span>
</td>

<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $release->released_by ?: '—' }}</td>

</tr>
@empty
<tr>
<td colspan="7" class="px-6 py-16 text-center">
<svg class="mx-auto size-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
<p class="mt-3 text-sm font-medium text-gray-900">No subsidy releases found</p>
<p class="mt-1 text-sm text-gray-500">{{ $hasFilters ? 'Try adjusting your filters.' : 'Released subsidies will appear here.' }}</p>
</td>
</tr>
@endforelse

</tbody>
</table>
</div>

@if ($releases->hasPages())
<div class="border-t border-gray-100 px-6 py-4">{{ $releases->links() }}</div>
@endif

</div>

</div>

</x-app-layout>