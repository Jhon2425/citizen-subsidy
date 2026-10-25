<x-app-layout>
    <div class="p-6 lg:p-8">

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Login History</h1>
                <p class="mt-1 text-sm text-gray-500">See which accounts signed in to the system, when, and from where.</p>
            </div>

            <form method="GET" action="{{ route('login-history.index') }}" class="flex gap-2">
                <input
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search name, email, or IP"
                    class="w-full sm:w-72 rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-[oklch(45%_0.15_151.711)] focus:outline-none focus:ring-2 focus:ring-[oklch(79.2%_0.209_151.711)]/40"
                >
                <button type="submit" class="rounded-xl bg-[oklch(45%_0.15_151.711)] px-4 py-2 text-sm font-semibold text-white hover:bg-[oklch(40%_0.14_151.711)] transition-colors">
                    Search
                </button>
                @if ($search !== '')
                    <a href="{{ route('login-history.index') }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-600">
                        <tr>
                            <th class="px-5 py-3">Account</th>
                            <th class="px-5 py-3">IP address</th>
                            <th class="px-5 py-3">Browser and device</th>
                            <th class="px-5 py-3">Signed in</th>
                            <th class="px-5 py-3">Signed out</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($histories as $history)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-[oklch(79.2%_0.209_151.711)] font-bold text-[oklch(35%_0.12_151.711)]">
                                            {{ strtoupper(substr($history->user?->name ?? '?', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-gray-900">{{ $history->user?->name ?? 'Deleted account' }}</p>
                                            <p class="truncate text-xs text-gray-500">{{ $history->user?->email ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-gray-700">{{ $history->ip_address ?? '-' }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-gray-700">{{ $history->browser }} on {{ $history->platform }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                    {{ $history->logged_in_at->format('M d, Y g:i A') }}
                                    <span class="block text-xs text-gray-400">{{ $history->logged_in_at->diffForHumans() }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                    @if ($history->logged_out_at)
                                        {{ $history->logged_out_at->format('M d, Y g:i A') }}
                                    @else
                                        <span class="text-gray-400">No sign-out recorded</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-gray-500">
                                    @if ($search !== '')
                                        No sign-ins match "{{ $search }}". Try a different name, email, or IP address.
                                    @else
                                        No sign-ins recorded yet. New logins will appear here.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($histories->hasPages())
                <div class="border-t border-gray-200 px-5 py-3">
                    {{ $histories->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>