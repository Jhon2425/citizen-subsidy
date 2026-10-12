<x-app-layout>

@php
    $field = 'block rounded-xl border-gray-300 text-sm shadow-sm focus:border-[oklch(45%_0.15_151.711)] focus:ring-[oklch(45%_0.15_151.711)] disabled:bg-gray-100 disabled:text-gray-400';

    // Everything that differs per status lives here, so the markup stays simple.
    $statusMeta = [
        'pending'  => [
            'label' => 'Pending',
            'icon'  => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
            'on'    => 'bg-yellow-500 text-white shadow-sm',
            'card'  => 'border-yellow-400',
            'note'  => 'bg-yellow-50 text-yellow-800 ring-yellow-100',
            'badge' => 'bg-yellow-100 text-yellow-700',
        ],
        'approved' => [
            'label' => 'Approved',
            'icon'  => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'on'    => 'bg-green-600 text-white shadow-sm',
            'card'  => 'border-green-500',
            'note'  => 'bg-green-50 text-green-800 ring-green-100',
            'badge' => 'bg-green-100 text-green-700',
        ],
        'rejected' => [
            'label' => 'Rejected',
            'icon'  => 'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'on'    => 'bg-red-600 text-white shadow-sm',
            'card'  => 'border-red-500',
            'note'  => 'bg-red-50 text-red-800 ring-red-100',
            'badge' => 'bg-red-100 text-red-700',
        ],
    ];

    $currentStatus = request('status');

    // Previous PSGC selection, restored after a validation error.
    $oldPsgc = [
        'region'       => old('region_code'),
        'province'     => old('province_code'),
        'municipality' => old('municipality_code'),
        'barangay'     => old('barangay_code'),
    ];
@endphp

<div x-data="{
    // ---- Register New modal ----
    showModal: {{ $errors->any() ? 'true' : 'false' }},
    formStatus: '{{ old('status', 'pending') }}',
    get needsReason() { return ['pending', 'rejected'].includes(this.formStatus); },

    // ---- Status change modal ----
    statusModal: false,
    statusForm: null,
    statusTarget: '',
    statusName: '',
    statusReason: '',
    saving: false,

    get statusNeedsReason() { return this.statusTarget !== 'approved'; },

    pickStatus(btn, status) {
        if (this.saving || status === btn.dataset.original) return;
        this.statusForm = btn.closest('form');
        this.statusTarget = status;
        this.statusName = btn.dataset.name;
        this.statusReason = status === btn.dataset.originalReasonStatus ? (btn.dataset.reason || '') : '';
        this.statusModal = true;
    },

    cancelStatus() {
        if (this.saving) return;
        this.statusModal = false;
        this.statusForm = null;
    },

    confirmStatus() {
        if (this.saving || !this.statusForm) return;
        if (this.statusNeedsReason && !this.statusReason.trim()) return;

        this.statusForm.querySelector('[name=status]').value = this.statusTarget;
        this.statusForm.querySelector('[name=reason]').value = this.statusNeedsReason ? this.statusReason.trim() : '';
        this.saving = true;
        this.statusForm.submit();
    },

    // ---- Delete modal ----
    deleteModal: false,
    deleteTarget: null,
    deleting: false,

    openDelete(btn) {
        this.deleteTarget = { name: btn.dataset.name, url: btn.dataset.deleteUrl };
        this.deleting = false;
        this.deleteModal = true;
    },
}" class="max-w-7xl mx-auto">

{{-- =====================================================
    BANNER + REGISTER
====================================================== --}}
<div class="relative mb-6 overflow-hidden rounded-2xl bg-[oklch(45%_0.15_151.711)] px-6 py-7 text-white sm:px-8 sm:py-9">
<svg class="pointer-events-none absolute -right-6 -bottom-10 size-48 text-white/10" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.5 4.5 0 100-9 4.5 4.5 0 000 9zm0 2c-4.14 0-7.5 2.24-7.5 5v1.5h15V19c0-2.76-3.36-5-7.5-5z"/></svg>
<div class="flex flex-wrap items-start justify-between gap-4">
<div>
<h1 class="text-2xl font-bold sm:text-3xl">Senior Citizen Applications</h1>
<p class="mt-1 max-w-xl text-sm text-white/80">Review, approve, or reject senior citizen registrations.</p>
<p class="mt-4 text-sm text-white/90">{{ $applications->total() }} {{ \Illuminate\Support\Str::plural('application', $applications->total()) }} {{ request()->hasAny(['search', 'status']) ? 'match your filters' : 'in total' }}</p>
</div>
<button type="button" x-on:click="showModal = true" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-[oklch(38%_0.15_151.711)] shadow-sm transition hover:bg-green-50">
<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
Register New
</button>
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


{{-- =====================================================
    FILTERS: status chips + search
====================================================== --}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">

<div class="flex flex-wrap gap-2" role="group" aria-label="Filter by status">
@php
    $chipBase = 'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium ring-1 transition';
    $chipOff  = 'bg-white text-gray-600 ring-gray-200 hover:bg-gray-50';
    $chipOn   = 'bg-gray-900 text-white ring-gray-900';
@endphp
<a href="{{ route('applications.index', array_filter(['search' => request('search')])) }}"
   class="{{ $chipBase }} {{ $currentStatus ? $chipOff : $chipOn }}">All</a>
@foreach ($statusMeta as $key => $meta)
<a href="{{ route('applications.index', array_filter(['search' => request('search'), 'status' => $key])) }}"
   class="{{ $chipBase }} {{ $currentStatus === $key ? $chipOn : $chipOff }}">
<svg class="size-4 {{ $currentStatus === $key ? '' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['icon'] }}" /></svg>
{{ $meta['label'] }}
</a>
@endforeach
</div>

<form method="GET" action="{{ route('applications.index') }}" class="flex w-full items-center gap-2 sm:w-auto">
@if ($currentStatus)
<input type="hidden" name="status" value="{{ $currentStatus }}">
@endif
<div class="relative w-full sm:w-72">
<label for="search" class="sr-only">Search by name</label>
<svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
<input type="search" name="search" id="search" value="{{ request('search') }}" placeholder="Search by name" class="{{ $field }} w-full pl-9">
</div>
<button type="submit" class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">Search</button>
@if (request()->hasAny(['search', 'status']))
<a href="{{ route('applications.index') }}" class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">Clear</a>
@endif
</form>
</div>


{{-- =====================================================
    APPLICATION CARDS
====================================================== --}}
@if ($applications->isEmpty())

<div class="rounded-2xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
<svg class="mx-auto size-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
<p class="mt-3 text-sm font-medium text-gray-900">No applications found</p>
<p class="mt-1 text-sm text-gray-500">{{ request()->hasAny(['search', 'status']) ? 'Try adjusting your filters.' : 'New registrations will appear here.' }}</p>
<button type="button" x-on:click="showModal = true" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-[oklch(45%_0.15_151.711)] px-4 py-2 text-sm font-medium text-white hover:bg-[oklch(38%_0.15_151.711)]">Register New</button>
</div>

@else

<div class="grid gap-4 md:grid-cols-2">
@foreach ($applications as $application)
@php
    $status = in_array($application->status, ['pending', 'approved', 'rejected']) ? $application->status : 'pending';
    $meta   = $statusMeta[$status];
@endphp

<article class="flex flex-col rounded-2xl border border-gray-200 border-t-4 {{ $meta['card'] }} bg-white shadow-sm transition hover:shadow-md">

{{-- Header --}}
<div class="flex items-start gap-3 px-5 pt-5">
<div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[oklch(79.2%_0.209_151.711)] text-sm font-bold text-[oklch(35%_0.12_151.711)]">{{ $application->initials }}</div>

<div class="min-w-0 flex-1">
<h3 class="truncate text-base font-semibold text-gray-900">{{ $application->full_name }}</h3>
<p class="text-xs text-gray-500">
{{ ucfirst($application->gender) }}, {{ $application->age }} yrs old
<span class="text-gray-400">&nbsp;(born {{ $application->birth_date->format('M d, Y') }})</span>
</p>
</div>

{{-- Kebab menu --}}
<div class="relative shrink-0" x-data="{ open: false }" x-on:keydown.escape.window="open = false">
<button type="button" x-on:click="open = !open"
        class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-[oklch(45%_0.15_151.711)]"
        aria-label="Options for {{ $application->full_name }}" aria-haspopup="true" x-bind:aria-expanded="open">
<svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
</button>
<div x-show="open" x-cloak x-on:click.outside="open = false" x-transition.origin.top.right
     class="absolute right-0 z-20 mt-1 w-44 rounded-xl bg-white py-1 shadow-lg ring-1 ring-black/5" style="display: none;" role="menu">
<a href="{{ route('applications.edit', $application) }}" role="menuitem" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
<svg class="size-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
Edit application
</a>
<div class="my-1 border-t border-gray-100"></div>
<button type="button" role="menuitem"
        x-on:click="open = false; openDelete($el)"
        data-name="{{ $application->full_name }}"
        data-delete-url="{{ route('applications.destroy', $application) }}"
        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50">
<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
Delete
</button>
</div>
</div>
</div>

{{-- Details --}}
<dl class="grid grid-cols-1 gap-x-4 gap-y-3 px-5 pt-4 text-sm sm:grid-cols-2">
<div class="min-w-0">
<dt class="text-xs text-gray-500">Address</dt>
<dd class="mt-0.5 truncate font-medium text-gray-900">{{ $application->barangay }}</dd>
<dd class="truncate text-xs text-gray-400">{{ $application->municipality }}</dd>
</div>
<div class="min-w-0">
<dt class="text-xs text-gray-500">Subsidy</dt>
<dd class="mt-0.5 truncate font-medium text-gray-900">{{ $application->subsidy->name ?? '—' }}</dd>
</div>
<div class="min-w-0">
<dt class="text-xs text-gray-500">Contact</dt>
<dd class="mt-0.5 font-medium tabular-nums text-gray-900">{{ $application->contact_number ?: '—' }}</dd>
</div>
<div class="min-w-0">
<dt class="text-xs text-gray-500">Application no.</dt>
<dd class="mt-0.5 font-medium tabular-nums text-gray-900">#{{ $applications->firstItem() + $loop->index }}</dd>
</div>
</dl>

{{-- Reason note --}}
@if ($application->reason)
<div class="mx-5 mt-4 rounded-xl px-3.5 py-2.5 text-xs leading-relaxed ring-1 {{ $meta['note'] }}">
<span class="font-semibold">Reason:</span> {{ $application->reason }}
</div>
@endif

{{-- Status control --}}
<div class="mt-auto px-5 pb-5 pt-4">
<form method="POST" action="{{ route('applications.updateStatus', $application) }}">
@csrf
@method('PATCH')
<input type="hidden" name="status" value="{{ $status }}">
<input type="hidden" name="reason" value="">

<p class="mb-1.5 text-xs font-medium text-gray-500">Status</p>
<div class="grid grid-cols-3 gap-1 rounded-xl bg-gray-100 p-1" role="group" aria-label="Application status for {{ $application->full_name }}">
@foreach ($statusMeta as $key => $m)
<button type="button"
        x-on:click="pickStatus($el, '{{ $key }}')"
        x-bind:disabled="saving"
        data-original="{{ $status }}"
        data-name="{{ $application->full_name }}"
        data-reason="{{ $application->reason }}"
        data-original-reason-status="{{ $status }}"
        aria-pressed="{{ $status === $key ? 'true' : 'false' }}"
        class="inline-flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[oklch(45%_0.15_151.711)] disabled:cursor-not-allowed disabled:opacity-60 {{ $status === $key ? $m['on'] : 'text-gray-500 hover:bg-white hover:text-gray-800' }}">
<svg class="size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}" /></svg>
{{ $m['label'] }}
</button>
@endforeach
</div>
</form>
</div>

</article>
@endforeach
</div>

@if ($applications->hasPages())
<div class="mt-6">{{ $applications->links() }}</div>
@endif

@endif


{{-- =====================================================
    STATUS CHANGE MODAL (confirm approve / reason for pending or rejected)
====================================================== --}}
<div x-show="statusModal" x-cloak
     x-on:keydown.escape.window="cancelStatus()"
     class="fixed inset-0 z-[60] flex items-center justify-center p-4"
     style="display: none;" role="dialog" aria-modal="true">

<div x-show="statusModal" x-transition.opacity x-on:click="cancelStatus()" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

<div x-show="statusModal" x-transition.scale.95 class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">

<div class="flex items-start gap-3 px-5 pt-5">
<div class="flex size-10 shrink-0 items-center justify-center rounded-full"
     x-bind:class="{
        'bg-green-100 text-green-700': statusTarget === 'approved',
        'bg-red-100 text-red-700': statusTarget === 'rejected',
        'bg-yellow-100 text-yellow-700': statusTarget === 'pending'
     }">
<svg x-show="statusTarget === 'approved'" class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $statusMeta['approved']['icon'] }}" /></svg>
<svg x-show="statusTarget === 'rejected'" x-cloak class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $statusMeta['rejected']['icon'] }}" /></svg>
<svg x-show="statusTarget === 'pending'" x-cloak class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $statusMeta['pending']['icon'] }}" /></svg>
</div>
<div>
<h2 class="text-base font-bold text-gray-900"
    x-text="statusTarget === 'approved' ? 'Approve this application?' : (statusTarget === 'rejected' ? 'Reject this application?' : 'Mark as pending?')"></h2>
<p class="mt-0.5 text-sm text-gray-500">
<template x-if="statusTarget === 'approved'">
<span><span class="font-medium text-gray-700" x-text="statusName"></span> will be added to the Senior Citizens master list.</span>
</template>
<template x-if="statusTarget !== 'approved'">
<span>Tell us why <span class="font-medium text-gray-700" x-text="statusName"></span> is being marked <span class="font-medium text-gray-700" x-text="statusTarget"></span>.</span>
</template>
</p>
</div>
</div>

<div class="px-5 pt-4" x-show="statusNeedsReason">
<label for="status_reason" class="sr-only">Reason</label>
<textarea id="status_reason" rows="4" maxlength="500" x-model="statusReason"
          x-effect="if (statusModal && statusNeedsReason) $nextTick(() => $el.focus())"
          class="{{ $field }} w-full" placeholder="Type the reason here"></textarea>
<p class="mt-1 text-right text-xs tabular-nums text-gray-400" x-text="statusReason.length + ' / 500'"></p>
</div>

<div class="mt-2 flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4">
<button type="button" x-on:click="cancelStatus()" x-bind:disabled="saving"
        class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50">Cancel</button>
<button type="button" x-on:click="confirmStatus()"
        x-bind:disabled="saving || (statusNeedsReason && !statusReason.trim())"
        class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-medium text-white shadow-sm transition disabled:cursor-not-allowed disabled:opacity-50"
        x-bind:class="{
            'bg-green-600 hover:bg-green-700': statusTarget === 'approved',
            'bg-red-600 hover:bg-red-700': statusTarget === 'rejected',
            'bg-yellow-500 hover:bg-yellow-600': statusTarget === 'pending'
        }">
<svg x-show="saving" x-cloak class="size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
<span x-text="saving ? 'Saving…' : (statusTarget === 'approved' ? 'Approve' : (statusTarget === 'rejected' ? 'Reject' : 'Mark as pending'))"></span>
</button>
</div>

</div>
</div>


{{-- =====================================================
    DELETE MODAL
====================================================== --}}
<div x-show="deleteModal" x-cloak
     x-on:keydown.escape.window="if (!deleting) deleteModal = false"
     class="fixed inset-0 z-[60] flex items-center justify-center p-4"
     style="display: none;" role="alertdialog" aria-modal="true">

<div x-show="deleteModal" x-transition.opacity x-on:click="if (!deleting) deleteModal = false" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

<div x-show="deleteModal" x-transition.scale.95 class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
<template x-if="deleteTarget">
<form method="POST" x-bind:action="deleteTarget.url" x-on:submit="deleting = true">
@csrf
@method('DELETE')
<div class="flex size-11 items-center justify-center rounded-full bg-red-100 text-red-600">
<svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
</div>
<h3 class="mt-4 text-base font-semibold text-gray-900">Delete this application?</h3>
<p class="mt-1 text-sm text-gray-600">The application for <span class="font-medium" x-text="deleteTarget.name"></span> will be removed permanently. This cannot be undone.</p>
<div class="mt-6 flex justify-end gap-3">
<button type="button" x-on:click="deleteModal = false" x-bind:disabled="deleting" class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 disabled:opacity-40">Cancel</button>
<button type="submit" x-bind:disabled="deleting" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-700 disabled:opacity-60" x-text="deleting ? 'Deleting…' : 'Delete application'"></button>
</div>
</form>
</template>
</div>
</div>


{{-- =====================================================
    REGISTER NEW — MODAL
====================================================== --}}
<div x-show="showModal"
     x-cloak
     x-on:keydown.escape.window="showModal = false"
     class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:items-center sm:p-6"
     style="display: none;">

<div x-show="showModal" x-transition.opacity x-on:click="showModal = false" class="fixed inset-0 bg-gray-900/50"></div>

<div x-show="showModal"
     x-transition:enter="ease-out duration-200"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     class="relative w-full max-w-2xl rounded-2xl bg-white shadow-xl">

<div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
<div>
<h2 class="text-lg font-bold text-gray-900">Register New Application</h2>
<p class="text-sm text-gray-500">Fill out the applicant's details below.</p>
</div>
<button type="button" x-on:click="showModal = false" class="text-gray-400 hover:text-gray-600" aria-label="Close">
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

<form method="POST" action="{{ route('applications.store') }}" id="application-form">
@csrf

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

<div>
<label for="last_name" class="text-sm font-medium text-gray-700">Last Name</label>
<input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required class="{{ $field }} mt-1 w-full" placeholder="Dela Cruz">
</div>

<div>
<label for="first_name" class="text-sm font-medium text-gray-700">First Name</label>
<input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required class="{{ $field }} mt-1 w-full" placeholder="Juan">
</div>

<div>
<label for="middle_name" class="text-sm font-medium text-gray-700">Middle Name <span class="text-gray-400">(optional)</span></label>
<input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name') }}" class="{{ $field }} mt-1 w-full" placeholder="Santos">
</div>

<div>
<label for="name_extension" class="text-sm font-medium text-gray-700">Name Extension <span class="text-gray-400">(optional)</span></label>
<input type="text" name="name_extension" id="name_extension" value="{{ old('name_extension') }}" list="name-extensions" maxlength="10" class="{{ $field }} mt-1 w-full" placeholder="Jr., Sr., II, III…">
<datalist id="name-extensions">
<option value="Jr."></option>
<option value="Sr."></option>
<option value="I"></option>
<option value="II"></option>
<option value="III"></option>
<option value="IV"></option>
<option value="V"></option>
</datalist>
</div>

<div class="sm:col-span-2">
<label class="text-sm font-medium text-gray-700">Birth Date</label>
<div class="mt-1 grid grid-cols-3 gap-2">
<select id="birth_month" class="{{ $field }} w-full" required>
<option value="">Month</option>
</select>
<select id="birth_day" class="{{ $field }} w-full" required>
<option value="">Day</option>
</select>
<select id="birth_year" class="{{ $field }} w-full" required>
<option value="">Year</option>
</select>
</div>
<input type="hidden" name="birth_date" id="birth_date" value="{{ old('birth_date') }}">
<p class="mt-1 text-xs text-gray-400">Age is computed automatically from this date.</p>
</div>

<div>
<label for="gender" class="text-sm font-medium text-gray-700">Gender</label>
<select name="gender" id="gender" required class="{{ $field }} mt-1 w-full">
<option value="">Select gender</option>
<option value="male" @selected(old('gender') === 'male')>Male</option>
<option value="female" @selected(old('gender') === 'female')>Female</option>
</select>
</div>

<div>
<label for="subsidy_id" class="text-sm font-medium text-gray-700">Subsidy Type</label>
<select name="subsidy_id" id="subsidy_id" required class="{{ $field }} mt-1 w-full">
<option value="">{{ $subsidies->isEmpty() ? 'No active subsidies yet' : 'Select subsidy' }}</option>
@foreach ($subsidies as $subsidy)
<option value="{{ $subsidy->id }}" @selected((string) old('subsidy_id') === (string) $subsidy->id)>{{ $subsidy->name }}</option>
@endforeach
</select>
</div>

<div>
<label for="region_code" class="text-sm font-medium text-gray-700">Region</label>
<select id="region_code" name="region_code" data-required="true" class="{{ $field }} mt-1 w-full">
<option value="">Loading regions…</option>
</select>
<input type="hidden" name="region" id="region_name">
</div>

<div id="province-wrapper">
<label for="province_code" class="text-sm font-medium text-gray-700">Province</label>
<select id="province_code" name="province_code" class="{{ $field }} mt-1 w-full" disabled>
<option value="">Select a region first</option>
</select>
<input type="hidden" name="province" id="province_name">
</div>

<div>
<label for="municipality_code" class="text-sm font-medium text-gray-700">City / Municipality</label>
<select id="municipality_code" name="municipality_code" data-required="true" class="{{ $field }} mt-1 w-full" disabled>
<option value="">Select a province first</option>
</select>
<input type="hidden" name="municipality" id="municipality_name">
</div>

<div>
<label for="barangay_code" class="text-sm font-medium text-gray-700">Barangay</label>
<select id="barangay_code" name="barangay_code" data-required="true" class="{{ $field }} mt-1 w-full" disabled>
<option value="">Select a city/municipality first</option>
</select>
<input type="hidden" name="barangay" id="barangay_name">
</div>

<div class="sm:col-span-2">
<label for="address" class="text-sm font-medium text-gray-700">House No. / Street (optional)</label>
<input type="text" name="address" id="address" value="{{ old('address') }}" class="{{ $field }} mt-1 w-full" placeholder="123 Rizal St.">
</div>

<div>
<label for="contact_number" class="text-sm font-medium text-gray-700">Contact Number</label>
<input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}" class="{{ $field }} mt-1 w-full" placeholder="09XXXXXXXXX">
</div>

<div>
<label for="status_field" class="text-sm font-medium text-gray-700">Status</label>
<select name="status" id="status_field" x-model="formStatus" required class="{{ $field }} mt-1 w-full">
<option value="pending">Pending</option>
<option value="approved">Approved</option>
<option value="rejected">Rejected</option>
</select>
<p class="mt-1 text-xs text-gray-400">"Approved" adds the applicant directly to Senior Citizens.</p>
</div>

{{-- Reason: only shown (and sent) for pending / rejected --}}
<div class="sm:col-span-2" x-show="needsReason" x-transition x-cloak style="display: none;">
<label for="reason" class="text-sm font-medium text-gray-700">
Reason for <span x-text="formStatus"></span>
</label>
<textarea name="reason" id="reason" rows="3" maxlength="500"
          x-bind:required="needsReason"
          x-bind:disabled="!needsReason"
          class="{{ $field }} mt-1 w-full"
          placeholder="Explain why this application is pending or rejected">{{ old('reason') }}</textarea>
</div>

</div>

</form>

</div>

<div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
<button type="button" x-on:click="showModal = false" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancel</button>
<button type="submit" form="application-form" class="rounded-xl bg-[oklch(45%_0.15_151.711)] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[oklch(38%_0.15_151.711)]">Save Application</button>
</div>

</div>
</div>

</div>

@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Each entry is tried in order: the public PSGC API straight from the browser
    // first (no dependency on the server's outbound HTTP/SSL), then the Laravel route.
    const PSGC = 'https://psgc.gitlab.io/api';
    const routes = {
        regions:         [PSGC + '/regions/',
                          '{{ route('psgc.regions') }}'],
        provinces:       (c) => [PSGC + '/regions/' + c + '/provinces/',
                                 '{{ url('/psgc/regions') }}/' + c + '/provinces'],
        citiesInRegion:  (c) => [PSGC + '/regions/' + c + '/cities-municipalities/',
                                 '{{ url('/psgc/regions') }}/' + c + '/cities'],
        cities:          (c) => [PSGC + '/provinces/' + c + '/cities-municipalities/',
                                 '{{ url('/psgc/provinces') }}/' + c + '/cities'],
        barangays:       (c) => [PSGC + '/cities-municipalities/' + c + '/barangays/',
                                 '{{ url('/psgc/cities') }}/' + c + '/barangays'],
    };

    const fieldClass = @json($field);

    // Values from the last submit (so a validation error doesn't wipe the address)
    const oldValues = @json($oldPsgc);

    /* ------------------------------------------------------------------
     * Searchable dropdown (wraps a hidden <select>)
     * ------------------------------------------------------------------ */
    function makeSearchable(select) {
        const wrap = document.createElement('div');
        wrap.className = 'relative';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.style.display = 'none';
        select.tabIndex = -1;
        select.required = false;

        const input = document.createElement('input');
        input.type = 'text';
        input.autocomplete = 'off';
        input.className = fieldClass + ' mt-1 w-full';
        input.setAttribute('role', 'combobox');

        const list = document.createElement('ul');
        list.className = 'absolute z-[70] mt-1 hidden max-h-56 w-full overflow-auto rounded-xl border border-gray-200 bg-white py-1 text-sm shadow-lg';

        const err = document.createElement('p');
        err.className = 'mt-1 hidden text-xs text-red-600';

        wrap.appendChild(input);
        wrap.appendChild(list);
        wrap.appendChild(err);

        let shown = [];
        let active = -1;

        const realOptions = () => Array.from(select.options).filter((o) => o.value !== '');
        const currentLabel = () => {
            const o = select.options[select.selectedIndex];
            return o && o.value ? o.textContent : '';
        };
        const isOpen = () => !list.classList.contains('hidden');

        function highlight() {
            Array.from(list.children).forEach((li, i) => li.classList.toggle('bg-gray-100', i === active));
            if (list.children[active]) list.children[active].scrollIntoView({ block: 'nearest' });
        }

        function renderList(query) {
            const term = query.trim().toLowerCase();
            shown = realOptions().filter((o) => o.textContent.toLowerCase().includes(term));
            list.innerHTML = '';

            if (shown.length === 0) {
                const li = document.createElement('li');
                li.className = 'px-3 py-2 text-gray-400';
                li.textContent = 'No results';
                list.appendChild(li);
            }

            shown.forEach((o) => {
                const li = document.createElement('li');
                li.textContent = o.textContent;
                li.className = 'cursor-pointer px-3 py-2 hover:bg-gray-100' + (o.value === select.value ? ' font-semibold text-[oklch(38%_0.15_151.711)]' : '');
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    choose(o);
                });
                list.appendChild(li);
            });

            active = shown.length ? 0 : -1;
            highlight();
        }

        function open(query) {
            if (input.disabled) return;
            renderList(query || '');
            list.classList.remove('hidden');
        }

        function close() {
            list.classList.add('hidden');
            active = -1;
        }

        function choose(option) {
            select.value = option.value;
            select.dispatchEvent(new Event('change'));
            input.value = option.textContent;
            close();
        }

        input.addEventListener('focus', () => { input.select(); open(''); });
        input.addEventListener('click', () => { if (!isOpen()) open(''); });
        input.addEventListener('input', () => open(input.value));
        input.addEventListener('blur', () => { close(); input.value = currentLabel(); });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!isOpen()) open(input.value);
                else if (shown.length) { active = (active + 1) % shown.length; highlight(); }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (shown.length) { active = (active - 1 + shown.length) % shown.length; highlight(); }
            } else if (e.key === 'Enter') {
                if (isOpen()) {
                    e.preventDefault();
                    if (shown[active]) choose(shown[active]);
                }
            } else if (e.key === 'Escape' && isOpen()) {
                e.stopPropagation();
                close();
                input.value = currentLabel();
            }
        });

        select._refresh = function () {
            input.disabled = select.disabled;
            input.placeholder = select.options[0] ? select.options[0].textContent : '';
            input.value = currentLabel();
            input.required = select.dataset.required === 'true';
        };

        // Inline error with a Retry button, so a failed request is never a dead end
        select._setError = function (message, retry) {
            err.innerHTML = '';
            if (!message) { err.classList.add('hidden'); return; }
            err.appendChild(document.createTextNode(message + ' '));
            if (retry) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'font-semibold underline';
                btn.textContent = 'Retry';
                btn.addEventListener('click', retry);
                err.appendChild(btn);
            }
            err.classList.remove('hidden');
        };

        select._refresh();
    }

    function setRequired(select, flag) {
        if (flag) select.dataset.required = 'true';
        else delete select.dataset.required;
        if (select._refresh) select._refresh();
    }

    /* ------------------------------------------------------------------
     * Elements
     * ------------------------------------------------------------------ */
    const regionSelect       = document.getElementById('region_code');
    const provinceWrapper    = document.getElementById('province-wrapper');
    const provinceSelect     = document.getElementById('province_code');
    const municipalitySelect = document.getElementById('municipality_code');
    const barangaySelect     = document.getElementById('barangay_code');

    const regionName       = document.getElementById('region_name');
    const provinceName     = document.getElementById('province_name');
    const municipalityName = document.getElementById('municipality_name');
    const barangayName     = document.getElementById('barangay_name');

    [regionSelect, provinceSelect, municipalitySelect, barangaySelect].forEach(makeSearchable);

    /* ------------------------------------------------------------------
     * Data helpers
     * ------------------------------------------------------------------ */
    const pick = (o, keys) => {
        for (const k of keys) if (o && o[k] != null && o[k] !== '') return o[k];
        return null;
    };

    // Accepts {code,name} or common PSGC variants (provinceCode, cityName, brgyName, ...)
    function normalize(items) {
        const out = items.map((i) => ({
            code: String(pick(i, ['code', 'psgc_code', 'psgcCode', 'regionCode', 'provinceCode', 'cityCode',
                                  'municipalityCode', 'citymunCode', 'barangayCode', 'brgyCode', 'id']) ?? ''),
            name: String(pick(i, ['name', 'regionName', 'provinceName', 'cityName', 'municipalityName',
                                  'citymunName', 'barangayName', 'brgyName', 'barangay', 'city', 'province', 'region']) ?? ''),
        })).filter((i) => i.code && i.name);

        if (items.length && out.length === 0) {
            console.warn('PSGC: response items have unexpected keys. First item:', items[0]);
        }
        return out;
    }

    // FIX: PsgcController@provinces returns { provinces: [...], skip_province: bool },
    // so unwrap `provinces` as well as plain arrays and { data: [...] }.
    function extractArray(json) {
        if (Array.isArray(json)) return json;
        if (json && Array.isArray(json.provinces)) return json.provinces;
        if (json && Array.isArray(json.data)) return json.data;
        if (json && json.data && Array.isArray(json.data.data)) return json.data.data;
        return [];
    }

    async function fetchJson(urls, label) {
        const list = Array.isArray(urls) ? urls : [urls];
        let lastError;

        for (let i = 0; i < list.length; i++) {
            const url = list[i];
            try {
                const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!r.ok) throw new Error(label + ': HTTP ' + r.status + ' from ' + url);
                const items = normalize(extractArray(await r.json()));

                // PsgcService returns [] when ITS request fails, so an empty list from the
                // Laravel fallback can't be trusted. Only the public API can say "truly empty".
                if (items.length === 0 && i > 0) {
                    throw new Error(label + ': empty response from fallback ' + url);
                }

                return items.sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true }));
            } catch (e) {
                console.warn('PSGC: ' + url + ' failed, trying next source if any.', e);
                lastError = e;
            }
        }

        console.error('PSGC: all sources failed for ' + label, lastError);
        throw lastError;
    }

    // Ignore responses from requests that were superseded
    const tokens = new Map();
    const begin = (select) => { const t = (tokens.get(select) || 0) + 1; tokens.set(select, t); return t; };
    const isStale = (select, t) => tokens.get(select) !== t;

    function resetSelect(select, placeholder, disabled = true) {
        select.innerHTML = '<option value="">' + placeholder + '</option>';
        select.disabled = disabled;
        if (select._setError) select._setError(null);
        if (select._refresh) select._refresh();
    }

    function render(select, items, placeholder) {
        select.innerHTML = '<option value="">' + placeholder + '</option>';
        items.forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = item.code;
            opt.textContent = item.name;
            select.appendChild(opt);
        });
        select.disabled = items.length === 0;
        if (select._setError) select._setError(null);
        if (select._refresh) select._refresh();
    }

    function syncHiddenName(select, hiddenInput) {
        const opt = select.options[select.selectedIndex];
        hiddenInput.value = (opt && opt.value) ? opt.textContent : '';
    }

    // Loads items into a select. Returns the items, or null on error / superseded request.
    async function loadInto(select, url, placeholder, label) {
        const t = begin(select);
        resetSelect(select, 'Loading…');
        try {
            const items = await fetchJson(url, label);
            if (isStale(select, t)) return null;
            render(select, items, placeholder);
            return items;
        } catch (e) {
            if (isStale(select, t)) return null;
            resetSelect(select, 'Could not load ' + label);
            select._setError('Could not load ' + label + '.', () => loadInto(select, url, placeholder, label));
            return null;
        }
    }

    // Select a value only if that option exists (used when restoring old input)
    function setIfExists(select, value) {
        if (!value) return false;
        const exists = Array.from(select.options).some((o) => o.value === String(value));
        if (!exists) return false;
        select.value = String(value);
        if (select._refresh) select._refresh();
        return true;
    }

    /* ------------------------------------------------------------------
     * Cascade handlers (restore = old values to re-apply, or undefined)
     * ------------------------------------------------------------------ */
    async function onRegion(restore) {
        syncHiddenName(regionSelect, regionName);
        provinceName.value = '';
        municipalityName.value = '';
        barangayName.value = '';
        begin(provinceSelect); begin(municipalitySelect); begin(barangaySelect);
        resetSelect(municipalitySelect, 'Select a province first');
        resetSelect(barangaySelect, 'Select a city/municipality first');

        const code = regionSelect.value;
        if (!code) {
            resetSelect(provinceSelect, 'Select a region first');
            provinceWrapper.classList.remove('hidden');
            setRequired(provinceSelect, false);
            return;
        }

        const provinces = await loadInto(provinceSelect, routes.provinces(code), 'Select a province', 'provinces');
        if (provinces === null || regionSelect.value !== code) return;

        if (provinces.length === 0) {
            // No provinces (e.g. NCR): go straight to cities
            // Keep the field visible but disabled so it never "disappears".
            provinceWrapper.classList.remove('hidden');
            setRequired(provinceSelect, false);
            resetSelect(provinceSelect, 'Not applicable (no provinces)');
            const cities = await loadInto(municipalitySelect, routes.citiesInRegion(code), 'Select a city/municipality', 'cities');
            if (cities && regionSelect.value === code && restore && setIfExists(municipalitySelect, restore.municipality)) {
                await onMunicipality(restore);
            }
        } else {
            provinceWrapper.classList.remove('hidden');
            setRequired(provinceSelect, true);
            if (restore && setIfExists(provinceSelect, restore.province)) {
                await onProvince(restore);
            }
        }
    }

    async function onProvince(restore) {
        syncHiddenName(provinceSelect, provinceName);
        municipalityName.value = '';
        barangayName.value = '';
        begin(barangaySelect);
        resetSelect(barangaySelect, 'Select a city/municipality first');

        const code = provinceSelect.value;
        if (!code) {
            begin(municipalitySelect);
            resetSelect(municipalitySelect, 'Select a province first');
            return;
        }

        const cities = await loadInto(municipalitySelect, routes.cities(code), 'Select a city/municipality', 'cities');
        if (cities && provinceSelect.value === code && restore && setIfExists(municipalitySelect, restore.municipality)) {
            await onMunicipality(restore);
        }
    }

    async function onMunicipality(restore) {
        syncHiddenName(municipalitySelect, municipalityName);
        barangayName.value = '';

        const code = municipalitySelect.value;
        if (!code) {
            begin(barangaySelect);
            resetSelect(barangaySelect, 'Select a city/municipality first');
            return;
        }

        const barangays = await loadInto(barangaySelect, routes.barangays(code), 'Select a barangay', 'barangays');
        if (barangays && municipalitySelect.value === code && restore && setIfExists(barangaySelect, restore.barangay)) {
            syncHiddenName(barangaySelect, barangayName);
        }
    }

    regionSelect.addEventListener('change', () => onRegion());
    provinceSelect.addEventListener('change', () => onProvince());
    municipalitySelect.addEventListener('change', () => onMunicipality());
    barangaySelect.addEventListener('change', () => syncHiddenName(barangaySelect, barangayName));

    /* ------------------------------------------------------------------
     * Initial load: regions (and restore old selection if any)
     * ------------------------------------------------------------------ */
    (async function init() {
        const regions = await loadInto(regionSelect, routes.regions, 'Select a region', 'regions');
        if (regions && setIfExists(regionSelect, oldValues.region)) {
            await onRegion(oldValues);
        }
    })();

    /* ------------------------------------------------------------------
     * Birth date: Month / Day / Year selects feeding a hidden `birth_date`
     * ------------------------------------------------------------------ */
    const monthSelect      = document.getElementById('birth_month');
    const daySelect        = document.getElementById('birth_day');
    const yearSelect       = document.getElementById('birth_year');
    const birthDateHidden  = document.getElementById('birth_date');

    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'];

    monthNames.forEach(function (name, index) {
        const opt = document.createElement('option');
        opt.value = String(index + 1).padStart(2, '0');
        opt.textContent = name;
        monthSelect.appendChild(opt);
    });

    const currentYear = new Date().getFullYear();
    const startYear = currentYear - 60;
    const endYear = currentYear - 130;
    for (let y = startYear; y >= endYear; y--) {
        const opt = document.createElement('option');
        opt.value = String(y);
        opt.textContent = String(y);
        yearSelect.appendChild(opt);
    }

    function daysInMonth(month, year) {
        if (!month) return 31;
        return new Date(year || 2000, month, 0).getDate();
    }

    function renderDays() {
        const month = parseInt(monthSelect.value, 10);
        const year = parseInt(yearSelect.value, 10);
        const previouslySelected = daySelect.value;
        const total = daysInMonth(month, year);

        daySelect.innerHTML = '<option value="">Day</option>';
        for (let d = 1; d <= total; d++) {
            const opt = document.createElement('option');
            opt.value = String(d).padStart(2, '0');
            opt.textContent = String(d);
            daySelect.appendChild(opt);
        }

        if (previouslySelected && parseInt(previouslySelected, 10) <= total) {
            daySelect.value = previouslySelected;
        }
    }

    function syncBirthDateHidden() {
        if (monthSelect.value && daySelect.value && yearSelect.value) {
            birthDateHidden.value = yearSelect.value + '-' + monthSelect.value + '-' + daySelect.value;
        } else {
            birthDateHidden.value = '';
        }
    }

    monthSelect.addEventListener('change', function () { renderDays(); syncBirthDateHidden(); });
    yearSelect.addEventListener('change', function () { renderDays(); syncBirthDateHidden(); });
    daySelect.addEventListener('change', syncBirthDateHidden);

    renderDays();

    const existingBirthDate = birthDateHidden.value;
    if (existingBirthDate) {
        const parts = existingBirthDate.split('-');
        if (parts.length === 3) {
            const [y, m, d] = parts;
            yearSelect.value = y;
            monthSelect.value = m;
            renderDays();
            daySelect.value = d;
        }
    }
});
</script>
@endonce

</x-app-layout>