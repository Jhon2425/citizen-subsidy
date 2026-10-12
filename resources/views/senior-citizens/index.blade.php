<x-app-layout>

@php
    $field = 'block rounded-xl border-gray-300 text-sm shadow-sm focus:border-[oklch(45%_0.15_151.711)] focus:ring-[oklch(45%_0.15_151.711)] disabled:bg-gray-100 disabled:text-gray-400';
    $label = 'block text-sm font-medium text-gray-700';

    // Everything the Alpine component needs, built once on the server.
    $items = $seniors->getCollection()->mapWithKeys(fn ($s) => [
        $s->id => [
            'id'             => $s->id,
            'name'           => $s->list_name,
            'initials'       => $s->initials,
            'gender'         => $s->gender,
            'gender_label'   => ucfirst($s->gender),
            'birth_date'     => optional($s->birth_date)->format('Y-m-d'),
            'birth_label'    => optional($s->birth_date)->format('F d, Y'),
            'age'            => $s->age,
            'contact_number' => $s->contact_number,
            'address_line'   => $s->address,
            'barangay'       => $s->barangay,
            'municipality'   => $s->municipality,
            'province'       => $s->province,
            'region'         => $s->region,
            'region_code'    => $s->region_code,
            'province_code'  => $s->province_code,
            'municipality_code' => $s->municipality_code,
            'barangay_code'  => $s->barangay_code,
            'is_active'      => (bool) $s->is_active,
            'update_url'     => route('senior-citizens.update', $s),
            'deceased_url'   => route('senior-citizens.deceased', $s),
        ],
    ]);

    $importErrors = $errors->getBag('import');

    $config = [
        'items'     => $items,
        'hasErrors' => $errors->any(),
        'importHasErrors' => $importErrors->any(),
        'routes'    => [
            'regions'        => route('psgc.regions'),
            'provinces'      => url('/psgc/regions') . '/{code}/provinces',
            'citiesInRegion' => url('/psgc/regions') . '/{code}/cities',
            'cities'         => url('/psgc/provinces') . '/{code}/cities',
            'barangays'      => url('/psgc/cities') . '/{code}/barangays',
        ],
        'old' => [
            'id'             => old('senior_id'),
            'list_name'      => old('list_name', ''),
            'gender'         => old('gender', ''),
            'birth_date'     => old('birth_date', ''),
            'contact_number' => old('contact_number', ''),
            'address_line'   => old('address', ''),
            'is_active'      => old('is_active', '1'),
        ],
    ];
@endphp

<div x-data="seniorsPage(@js($config))" class="max-w-7xl mx-auto">

{{-- =====================================================
    BANNER
====================================================== --}}
<div class="relative mb-6 overflow-hidden rounded-2xl bg-[oklch(45%_0.15_151.711)] px-6 py-7 text-white sm:px-8 sm:py-9">
<svg class="pointer-events-none absolute -right-6 -bottom-10 size-48 text-white/10" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.5 4.5 0 100-9 4.5 4.5 0 000 9zm0 2c-4.14 0-7.5 2.24-7.5 5v1.5h15V19c0-2.76-3.36-5-7.5-5z"/></svg>
<div class="flex flex-wrap items-start justify-between gap-4">
<div>
<h1 class="text-2xl font-bold sm:text-3xl">Approved Senior Citizens</h1>
<p class="mt-1 max-w-xl text-sm text-white/80">Master list of approved senior citizens. Click a name to view or edit a record.</p>
<p class="mt-4 text-sm text-white/90">{{ $seniors->total() }} {{ \Illuminate\Support\Str::plural('senior citizen', $seniors->total()) }} {{ request()->hasAny(['search', 'barangay']) ? 'match your filters' : 'in total' }}</p>
</div>

<div class="relative flex flex-wrap items-center gap-2">
{{-- Import --}}
<button type="button" x-on:click="openImport()"
        class="inline-flex items-center gap-2 rounded-xl border border-white/40 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
Import
</button>

{{-- Export --}}
<a href="#" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-[oklch(38%_0.15_151.711)] shadow-sm transition hover:bg-green-50">
<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
Export
</a>
</div>
</div>
</div>


@if (session('success'))
<div x-data="{ show: true }" x-show="show" x-transition class="mb-6 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 p-4">
<svg class="mt-0.5 size-5 shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
<p class="flex-1 text-sm font-medium text-green-800">{{ session('success') }}</p>
<button type="button" x-on:click="show = false" class="text-green-600 hover:text-green-800" aria-label="Dismiss">
<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
</button>
</div>
@endif

@if (session('error'))
<div x-data="{ show: true }" x-show="show" x-transition class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4">
<svg class="mt-0.5 size-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
<p class="flex-1 text-sm font-medium text-red-800">{{ session('error') }}</p>
<button type="button" x-on:click="show = false" class="text-red-600 hover:text-red-800" aria-label="Dismiss">
<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
</button>
</div>
@endif

{{-- Rows that were skipped during the last import --}}
@if (session('import_errors'))
<div x-data="{ show: true }" x-show="show" x-transition class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
<div class="flex items-start gap-3">
<svg class="mt-0.5 size-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
<div class="min-w-0 flex-1">
<p class="text-sm font-semibold text-amber-900">Some rows were skipped during import</p>
<ul class="mt-2 max-h-40 list-disc space-y-1 overflow-y-auto pl-5 text-sm text-amber-800">
@foreach (session('import_errors') as $importError)
<li>{{ $importError }}</li>
@endforeach
</ul>
</div>
<button type="button" x-on:click="show = false" class="text-amber-600 hover:text-amber-800" aria-label="Dismiss">
<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
</button>
</div>
</div>
@endif


{{-- =====================================================
    FILTERS
====================================================== --}}
<form method="GET" action="{{ route('senior-citizens.index') }}" class="mb-6 flex flex-wrap items-center gap-3">
<div class="w-full sm:w-52">
<label for="barangay" class="sr-only">Barangay</label>
<select name="barangay" id="barangay" class="{{ $field }} w-full" onchange="this.form.submit()">
<option value="">All Barangays</option>
@foreach ($barangays as $barangay)
<option value="{{ $barangay }}" @selected(request('barangay') === $barangay)>{{ $barangay }}</option>
@endforeach
</select>
</div>

<div class="relative w-full sm:w-72">
<label for="search" class="sr-only">Search by name</label>
<svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
<input type="search" name="search" id="search" value="{{ request('search') }}" placeholder="Search by name" class="{{ $field }} w-full pl-9">
</div>

<button type="submit" class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">Search</button>

@if (request()->hasAny(['search', 'barangay']))
<a href="{{ route('senior-citizens.index') }}" class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">Clear</a>
@endif
</form>


{{-- =====================================================
    SENIOR CITIZENS TABLE
====================================================== --}}
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-gray-200">

<thead class="bg-gray-50">
<tr>
<th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">No.</th>
<th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Full Name</th>
<th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Age</th>
<th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Address</th>
<th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Contact</th>
<th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
</tr>
</thead>

<tbody class="divide-y divide-gray-100 bg-white">

@forelse ($seniors as $senior)
<tr class="cursor-pointer transition hover:bg-gray-50 focus:bg-gray-50 focus:outline-none" tabindex="0" role="button" x-on:click="openView({{ $senior->id }})" x-on:keydown.enter="openView({{ $senior->id }})">

<td class="whitespace-nowrap px-6 py-4">
<p class="text-sm font-semibold tabular-nums text-gray-900">{{ $seniors->firstItem() + $loop->index }}</p>
</td>

<td class="whitespace-nowrap px-6 py-4">
<p class="truncate text-sm font-semibold text-gray-900">{{ $senior->list_name }}</p>
<p class="text-xs text-gray-500">{{ ucfirst($senior->gender) }}</p>
</td>

<td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
<span class="font-medium tabular-nums text-gray-900">{{ $senior->age }}</span>
<span class="block text-xs text-gray-400">{{ optional($senior->birth_date)->format('M d, Y') }}</span>
</td>

<td class="px-6 py-4 text-sm text-gray-600">
<p class="font-medium text-gray-900">{{ $senior->barangay }}</p>
<p class="text-xs text-gray-400">{{ $senior->municipality }}</p>
</td>

<td class="whitespace-nowrap px-6 py-4 text-sm tabular-nums text-gray-600">{{ $senior->contact_number ?: '—' }}</td>

<td class="whitespace-nowrap px-6 py-4">
@if ($senior->is_active)
<span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-semibold text-green-700"><span class="size-1.5 rounded-full bg-green-500"></span>Active</span>
@else
<span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-600"><span class="size-1.5 rounded-full bg-gray-400"></span>Inactive</span>
@endif
</td>


</tr>
@empty
<tr>
<td colspan="6" class="px-6 py-16 text-center">
<svg class="mx-auto size-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0112.728 0zM15.75 8.25a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM19.5 15a3 3 0 100-6 3 3 0 000 6z" /></svg>
<p class="mt-3 text-sm font-medium text-gray-900">No approved senior citizens found</p>
<p class="mt-1 text-sm text-gray-500">{{ request()->hasAny(['search', 'barangay']) ? 'Try adjusting your filters.' : 'Approved registrations will appear here.' }}</p>
</td>
</tr>
@endforelse

</tbody>
</table>
</div>

@if ($seniors->hasPages())
<div class="border-t border-gray-100 px-6 py-4">{{ $seniors->links() }}</div>
@endif

</div>


{{-- =====================================================
    IMPORT MODAL
====================================================== --}}
<div x-show="importOpen" x-cloak style="display: none;" x-on:keydown.escape.window="if (!importing) importOpen = false"
     class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:items-center sm:p-6" role="dialog" aria-modal="true">
<div x-show="importOpen" x-transition.opacity x-on:click="if (!importing) importOpen = false" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

<div x-show="importOpen" x-transition.scale.95 x-on:click.stop class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl">

<div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
<div>
<h2 class="text-lg font-bold text-gray-900">Import senior citizens</h2>
<p class="text-sm text-gray-500">Upload an Excel or CSV file to add records in bulk.</p>
</div>
<button type="button" x-on:click="importOpen = false" x-bind:disabled="importing" class="rounded-full p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
<svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
</button>
</div>

<form method="POST" action="{{ route('senior-citizens.import') }}" enctype="multipart/form-data" x-on:submit="importing = true">
@csrf

<div class="space-y-5 px-6 py-5">

@if ($importErrors->any())
<div class="rounded-xl border border-red-200 bg-red-50 p-4">
<ul class="list-disc space-y-1 pl-5 text-sm text-red-700">
@foreach ($importErrors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif

{{-- Expected columns --}}
<div class="rounded-xl bg-gray-50 p-4 text-sm text-gray-700">
<div class="flex items-start justify-between gap-3">
<p class="font-semibold text-gray-900">Required columns (first row)</p>
<a href="{{ route('senior-citizens.import.template') }}" class="shrink-0 text-xs font-semibold text-[oklch(45%_0.15_151.711)] hover:underline">Download template</a>
</div>
<p class="mt-2 font-mono text-xs leading-relaxed text-gray-600">list_name, gender, birth_date, contact_number, address, barangay, municipality, province, region</p>
<ul class="mt-3 list-disc space-y-1 pl-5 text-xs text-gray-500">
<li><span class="font-medium">list_name</span>, <span class="font-medium">gender</span>, <span class="font-medium">birth_date</span>, <span class="font-medium">barangay</span> and <span class="font-medium">municipality</span> are required.</li>
<li>Gender: male or female. Birth date: for example 1950-03-25.</li>
<li>Rows with the same name and birth date as an existing record are skipped.</li>
</ul>
</div>

{{-- File picker --}}
<div>
<label for="import_file" class="{{ $label }}">Excel or CSV file</label>
<div class="relative mt-1 flex flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-8 text-center transition"
     x-bind:class="dragging ? 'border-[oklch(45%_0.15_151.711)] bg-green-50' : 'border-gray-300 hover:border-gray-400'">
<svg class="size-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
<p class="mt-2 text-sm text-gray-600" x-show="!fileName">Drag a file here or <span class="font-semibold text-[oklch(45%_0.15_151.711)]">browse</span></p>
<p class="mt-2 text-sm font-medium text-gray-900" x-show="fileName" x-cloak x-text="fileName"></p>
<p class="mt-1 text-xs text-gray-400">.xlsx, .xls or .csv, up to 5 MB</p>

<input type="file" id="import_file" name="file" accept=".xlsx,.xls,.csv" required
       class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
       x-on:change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''"
       x-on:dragenter="dragging = true" x-on:dragleave="dragging = false" x-on:drop="dragging = false">
</div>
</div>

</div>

<div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
<button type="button" x-on:click="importOpen = false" x-bind:disabled="importing" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50">Cancel</button>
<button type="submit" x-bind:disabled="importing || !fileName"
        class="inline-flex items-center gap-2 rounded-xl bg-[oklch(45%_0.15_151.711)] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[oklch(38%_0.15_151.711)] disabled:cursor-not-allowed disabled:opacity-60">
<svg x-show="importing" x-cloak class="size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
<span x-text="importing ? 'Importing…' : 'Import file'"></span>
</button>
</div>

</form>
</div>
</div>


{{-- =====================================================
    VIEW MODAL
====================================================== --}}
<div x-show="viewItem" x-cloak style="display: none;" x-on:keydown.escape.window="viewItem = null"
     class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
<div x-show="viewItem" x-transition.opacity x-on:click="viewItem = null" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

<div x-show="viewItem" x-transition.scale.95 x-on:click.stop class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-xl">
<template x-if="viewItem">
<div>
<div class="flex items-start justify-between gap-4 px-6 pt-6">
<div class="flex items-center gap-4 min-w-0">
<div class="min-w-0">
<h3 class="truncate text-lg font-semibold text-gray-900" x-text="viewItem.name"></h3>
<span class="mt-1 inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
      x-bind:class="viewItem.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
      x-text="viewItem.is_active ? 'Active' : 'Inactive'"></span>
</div>
</div>
<button type="button" x-on:click="viewItem = null" class="shrink-0 rounded-full p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
<svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
</button>
</div>

<div class="space-y-5 px-6 py-5">
<div>
<p class="mb-2 text-sm font-semibold text-gray-900">Personal information</p>
<dl class="grid grid-cols-2 gap-4 rounded-xl bg-gray-50 p-4 text-sm">
<div><dt class="text-xs text-gray-500">Gender</dt><dd class="mt-0.5 font-medium text-gray-900" x-text="viewItem.gender_label || '—'"></dd></div>
<div><dt class="text-xs text-gray-500">Age</dt><dd class="mt-0.5 font-medium tabular-nums text-gray-900" x-text="viewItem.age + ' years old'"></dd></div>
<div><dt class="text-xs text-gray-500">Birth date</dt><dd class="mt-0.5 font-medium text-gray-900" x-text="viewItem.birth_label || '—'"></dd></div>
<div><dt class="text-xs text-gray-500">Contact number</dt><dd class="mt-0.5 font-medium tabular-nums text-gray-900" x-text="viewItem.contact_number || '—'"></dd></div>
</dl>
</div>

<div>
<p class="mb-2 text-sm font-semibold text-gray-900">Address</p>
<dl class="grid grid-cols-2 gap-4 rounded-xl bg-gray-50 p-4 text-sm">
<div class="col-span-2"><dt class="text-xs text-gray-500">House no. / street</dt><dd class="mt-0.5 font-medium text-gray-900" x-text="viewItem.address_line || '—'"></dd></div>
<div><dt class="text-xs text-gray-500">Barangay</dt><dd class="mt-0.5 font-medium text-gray-900" x-text="viewItem.barangay || '—'"></dd></div>
<div><dt class="text-xs text-gray-500">City / municipality</dt><dd class="mt-0.5 font-medium text-gray-900" x-text="viewItem.municipality || '—'"></dd></div>
<div><dt class="text-xs text-gray-500">Region</dt><dd class="mt-0.5 font-medium text-gray-900" x-text="viewItem.region || '—'"></dd></div>
</dl>
</div>
</div>

<div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
<button type="button" x-on:click="openDeceased(viewItem.id)" class="mr-auto rounded-xl px-4 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50">Mark deceased</button>
<button type="button" x-on:click="viewItem = null" class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100">Close</button>
<button type="button" x-on:click="openEdit(viewItem.id)" class="inline-flex items-center gap-2 rounded-xl bg-[oklch(45%_0.15_151.711)] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[oklch(38%_0.15_151.711)]">
<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
Edit information
</button>
</div>
</div>
</template>
</div>
</div>


{{-- =====================================================
    EDIT MODAL
====================================================== --}}
<div x-show="editOpen" x-cloak style="display: none;" x-on:keydown.escape.window="if (!saving) editOpen = false"
     class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:items-center sm:p-6" role="dialog" aria-modal="true">
<div x-show="editOpen" x-transition.opacity x-on:click="if (!saving) editOpen = false" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

<div x-show="editOpen" x-transition.scale.95 x-on:click.stop class="relative w-full max-w-2xl rounded-2xl bg-white shadow-xl">

<div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
<div>
<h2 class="text-lg font-bold text-gray-900">Edit senior citizen</h2>
<p class="text-sm text-gray-500" x-text="editItem ? editItem.name : ''"></p>
</div>
<button type="button" x-on:click="editOpen = false" x-bind:disabled="saving" class="rounded-full p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
<svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
</button>
</div>

<div class="max-h-[70vh] overflow-y-auto px-6 py-5">

@if ($errors->any())
<div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4">
<p class="text-sm font-medium text-red-800">Please fix the following:</p>
<ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
@foreach ($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif

<form method="POST" x-bind:action="editItem ? editItem.update_url : '#'" id="senior-edit-form" x-on:submit="saving = true">
@csrf
@method('PUT')
<input type="hidden" name="senior_id" x-bind:value="editId">

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

<div class="sm:col-span-2">
<label for="e_list_name" class="{{ $label }}">Full name</label>
<input type="text" id="e_list_name" name="list_name" x-model="form.list_name" required class="{{ $field }} mt-1 w-full" placeholder="Full name as shown in the list">
</div>

<div>
<label for="e_birth_date" class="{{ $label }}">Birth date</label>
<input type="date" id="e_birth_date" name="birth_date" x-model="form.birth_date" max="{{ now()->format('Y-m-d') }}" required class="{{ $field }} mt-1 w-full">
</div>

<div>
<label for="e_gender" class="{{ $label }}">Gender</label>
<select id="e_gender" name="gender" x-model="form.gender" required class="{{ $field }} mt-1 w-full">
<option value="">Select gender</option>
<option value="male">Male</option>
<option value="female">Female</option>
</select>
</div>

<div>
<label for="e_contact" class="{{ $label }}">Contact number</label>
<input type="text" id="e_contact" name="contact_number" x-model="form.contact_number" class="{{ $field }} mt-1 w-full" placeholder="09XXXXXXXXX">
</div>

<div>
<label for="e_active" class="{{ $label }}">Status</label>
<select id="e_active" name="is_active" x-model="form.is_active" class="{{ $field }} mt-1 w-full">
<option value="1">Active</option>
<option value="0">Inactive</option>
</select>
</div>

{{-- Address --}}
<div class="sm:col-span-2 border-t border-gray-100 pt-5">
<div class="flex items-center justify-between gap-3">
<p class="text-sm font-semibold text-gray-900">Address</p>
<button type="button" x-on:click="toggleAddress()" class="text-xs font-semibold text-[oklch(45%_0.15_151.711)] hover:underline"
        x-text="changeAddress ? 'Keep current address' : 'Change address'"></button>
</div>

<div x-show="!changeAddress" class="mt-2 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-700">
<span x-text="currentAddress"></span>
</div>
</div>

<div class="sm:col-span-2">
<label for="e_address" class="{{ $label }}">House no. / street <span class="text-gray-400">(optional)</span></label>
<input type="text" id="e_address" name="address" x-model="form.address_line" class="{{ $field }} mt-1 w-full" placeholder="123 Rizal St.">
</div>

<template x-if="changeAddress">
<div class="sm:col-span-2 grid grid-cols-1 gap-5 sm:grid-cols-2">

<p x-show="addr.error" x-cloak class="sm:col-span-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700" x-text="addr.error"></p>

<div>
<label for="e_region" class="{{ $label }}">Region</label>
<select id="e_region" name="region_code" x-model="addr.region_code" x-on:change="onRegion()" required class="{{ $field }} mt-1 w-full">
<option value="">Select a region</option>
<template x-for="r in addr.regions" x-bind:key="r.code"><option x-bind:value="r.code" x-text="r.name"></option></template>
</select>
<input type="hidden" name="region" x-bind:value="nameOf(addr.regions, addr.region_code)">
</div>

<div x-show="addr.hasProvince">
<label for="e_province" class="{{ $label }}">Province</label>
<select id="e_province" name="province_code" x-model="addr.province_code" x-on:change="onProvince()" x-bind:disabled="!addr.provinces.length" x-bind:required="addr.hasProvince" class="{{ $field }} mt-1 w-full">
<option value="">Select a province</option>
<template x-for="p in addr.provinces" x-bind:key="p.code"><option x-bind:value="p.code" x-text="p.name"></option></template>
</select>
<input type="hidden" name="province" x-bind:value="nameOf(addr.provinces, addr.province_code)">
</div>

<div>
<label for="e_city" class="{{ $label }}">City / municipality</label>
<select id="e_city" name="municipality_code" x-model="addr.municipality_code" x-on:change="onCity()" x-bind:disabled="!addr.cities.length" required class="{{ $field }} mt-1 w-full">
<option value="">Select a city/municipality</option>
<template x-for="c in addr.cities" x-bind:key="c.code"><option x-bind:value="c.code" x-text="c.name"></option></template>
</select>
<input type="hidden" name="municipality" x-bind:value="nameOf(addr.cities, addr.municipality_code)">
</div>

<div>
<label for="e_barangay" class="{{ $label }}">Barangay</label>
<select id="e_barangay" name="barangay_code" x-model="addr.barangay_code" x-bind:disabled="!addr.barangays.length" required class="{{ $field }} mt-1 w-full">
<option value="">Select a barangay</option>
<template x-for="b in addr.barangays" x-bind:key="b.code"><option x-bind:value="b.code" x-text="b.name"></option></template>
</select>
<input type="hidden" name="barangay" x-bind:value="nameOf(addr.barangays, addr.barangay_code)">
</div>

</div>
</template>

</div>
</form>

</div>

<div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
<button type="button" x-on:click="editOpen = false" x-bind:disabled="saving" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50">Cancel</button>
<button type="submit" form="senior-edit-form" x-bind:disabled="saving"
        class="inline-flex items-center gap-2 rounded-xl bg-[oklch(45%_0.15_151.711)] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[oklch(38%_0.15_151.711)] disabled:cursor-not-allowed disabled:opacity-60">
<svg x-show="saving" x-cloak class="size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
<span x-text="saving ? 'Saving…' : 'Save changes'"></span>
</button>
</div>

</div>
</div>


{{-- =====================================================
    MARK DECEASED MODAL
====================================================== --}}
<div x-show="deceasedItem" x-cloak style="display: none;" x-on:keydown.escape.window="deceasedItem = null"
     class="fixed inset-0 z-[60] flex items-center justify-center p-4" role="alertdialog" aria-modal="true">
<div x-show="deceasedItem" x-transition.opacity x-on:click="deceasedItem = null" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

<div x-show="deceasedItem" x-transition.scale.95 class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
<template x-if="deceasedItem">
<form method="POST" x-bind:action="deceasedItem.deceased_url" x-on:submit="marking = true">
@csrf
@method('PATCH')
<div class="flex size-11 items-center justify-center rounded-full bg-red-100 text-red-600">
<svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
</div>
<h3 class="mt-4 text-base font-semibold text-gray-900">Mark as deceased?</h3>
<p class="mt-1 text-sm text-gray-600"><span class="font-medium" x-text="deceasedItem.name"></span> will be removed from this list and will no longer receive subsidy announcements.</p>
<div class="mt-6 flex justify-end gap-3">
<button type="button" x-on:click="deceasedItem = null" x-bind:disabled="marking" class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 disabled:opacity-40">Cancel</button>
<button type="submit" x-bind:disabled="marking" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-700 disabled:opacity-60" x-text="marking ? 'Saving…' : 'Mark deceased'"></button>
</div>
</form>
</template>
</div>
</div>

</div>

<script>
function seniorsPage(cfg) {
    const blankForm = () => ({
        list_name: '', gender: '', birth_date: '',
        contact_number: '', address_line: '', is_active: '1',
    });

    const emptyAddr = () => ({
        regions: [], provinces: [], cities: [], barangays: [],
        region_code: '', province_code: '', municipality_code: '', barangay_code: '',
        hasProvince: true, error: '',
    });

    const restoring = cfg.hasErrors && cfg.old.id;

    return {
        items: cfg.items,

        viewItem: null,
        deceasedItem: null,
        marking: false,

        // ---- import ----
        importOpen: !!cfg.importHasErrors,
        importing: false,
        dragging: false,
        fileName: '',

        openImport() {
            this.importing = false;
            this.dragging = false;
            this.fileName = '';
            this.importOpen = true;
        },

        editOpen: !!restoring,
        editId: restoring ? cfg.old.id : null,
        saving: false,
        changeAddress: false,
        addr: emptyAddr(),

        // Every key exists from the start so the inputs stay reactive.
        form: restoring ? {
            list_name: cfg.old.list_name || '',
            gender: String(cfg.old.gender || '').toLowerCase(),
            birth_date: cfg.old.birth_date || '',
            contact_number: cfg.old.contact_number || '',
            address_line: cfg.old.address_line || '',
            is_active: String(cfg.old.is_active ?? '1'),
        } : blankForm(),

        get editItem() { return this.editId ? (this.items[this.editId] || null) : null; },

        get currentAddress() {
            const a = this.editItem;
            if (!a) return '';
            return [a.barangay, a.municipality, a.province, a.region].filter(Boolean).join(', ') || 'No address on file';
        },

        // ---- view / mark deceased ----
        openView(id) { this.viewItem = this.items[id] || null; },
        openDeceased(id) { this.marking = false; this.viewItem = null; this.deceasedItem = this.items[id] || null; },

        // ---- edit: fill the form with the saved record ----
        openEdit(id) {
            const s = this.items[id];
            if (!s) return;

            this.editId = id;
            this.form = {
                list_name: s.name || '',
                gender: String(s.gender || '').toLowerCase(),
                birth_date: s.birth_date || '',
                contact_number: s.contact_number || '',
                address_line: s.address_line || '',
                is_active: s.is_active ? '1' : '0',
            };
            this.changeAddress = false;
            this.addr = emptyAddr();
            this.saving = false;
            this.viewItem = null;
            this.editOpen = true;
        },

        // ---- address cascade (PSGC) ----
        url(key, code) { return cfg.routes[key].replace('{code}', code); },

        async getJson(url) {
            try {
                const r = await fetch(url);
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const j = await r.json();
                return Array.isArray(j) ? j : (Array.isArray(j.data) ? j.data : []);
            } catch (e) {
                console.error(e);
                this.addr.error = 'Could not load address options. Please try again.';
                return [];
            }
        },

        nameOf(list, code) {
            const f = list.find(i => String(i.code) === String(code));
            return f ? f.name : '';
        },

        async toggleAddress() {
            this.changeAddress = !this.changeAddress;
            if (!this.changeAddress) { this.addr = emptyAddr(); return; }

            this.addr.regions = await this.getJson(cfg.routes.regions);
            await this.prefillAddress();
        },

        // Pre-select the saved region / province / city / barangay.
        async prefillAddress() {
            const it = this.editItem;
            if (!it || !it.region_code) return;
            const a = this.addr;

            await this.$nextTick();
            a.region_code = String(it.region_code);

            a.provinces = await this.getJson(this.url('provinces', a.region_code));
            a.hasProvince = a.provinces.length > 0;

            if (a.hasProvince) {
                await this.$nextTick();
                a.province_code = it.province_code ? String(it.province_code) : '';
                if (a.province_code) a.cities = await this.getJson(this.url('cities', a.province_code));
            } else {
                a.cities = await this.getJson(this.url('citiesInRegion', a.region_code));
            }

            await this.$nextTick();
            a.municipality_code = it.municipality_code ? String(it.municipality_code) : '';

            if (a.municipality_code) {
                a.barangays = await this.getJson(this.url('barangays', a.municipality_code));
                await this.$nextTick();
                a.barangay_code = it.barangay_code ? String(it.barangay_code) : '';
            }
        },

        async onRegion() {
            Object.assign(this.addr, { provinces: [], cities: [], barangays: [], province_code: '', municipality_code: '', barangay_code: '', error: '' });
            if (!this.addr.region_code) return;

            const provinces = await this.getJson(this.url('provinces', this.addr.region_code));
            this.addr.provinces = provinces;
            this.addr.hasProvince = provinces.length > 0;

            // Regions without provinces (e.g. NCR) go straight to cities.
            if (!provinces.length) {
                this.addr.cities = await this.getJson(this.url('citiesInRegion', this.addr.region_code));
            }
        },

        async onProvince() {
            Object.assign(this.addr, { cities: [], barangays: [], municipality_code: '', barangay_code: '' });
            if (!this.addr.province_code) return;
            this.addr.cities = await this.getJson(this.url('cities', this.addr.province_code));
        },

        async onCity() {
            Object.assign(this.addr, { barangays: [], barangay_code: '' });
            if (!this.addr.municipality_code) return;
            this.addr.barangays = await this.getJson(this.url('barangays', this.addr.municipality_code));
        },
    };
}
</script>

</x-app-layout>