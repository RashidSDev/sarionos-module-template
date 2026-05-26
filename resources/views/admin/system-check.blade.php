<x-app-layout title="{{ $moduleName }} System Check">
    <div class="min-h-screen bg-gray-100 px-4 py-4 sm:px-6 sm:py-4">
        <div class="w-full space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">{{ $moduleName }} System Check</h1>
                        <p class="mt-1 text-sm text-gray-600">
                            Checks queue, storage, deployment files, and required module infrastructure.
                        </p>
                    </div>

                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Back to {{ $moduleName }}
                    </a>
                </div>
            </div>

            @if(session('status'))
                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm font-semibold text-blue-800">
                    {{ session('status') }}
                </div>
            @endif

            @if($latestHeartbeat)
                <div class="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">
                                Queue heartbeat status:
                                <span class="{{ $latestHeartbeat->status === 'completed' ? 'text-emerald-700' : ($latestHeartbeat->status === 'failed' ? 'text-red-700' : 'text-blue-700') }}">
                                    {{ strtoupper($latestHeartbeat->status) }}
                                </span>
                            </div>

                            <div class="mt-1 text-sm text-gray-600">
                                {{ $latestHeartbeat->current_step ?: 'No current step.' }}
                            </div>

                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                                <div
                                    class="h-full rounded-full {{ $latestHeartbeat->status === 'failed' ? 'bg-red-500' : 'bg-blue-600' }}"
                                    style="width: {{ (int) $latestHeartbeat->progress_percent }}%"
                                ></div>
                            </div>

                            <div class="mt-1 text-xs text-gray-500">
                                Progress: {{ (int) $latestHeartbeat->progress_percent }}%
                                · Updated: {{ $latestHeartbeat->updated_at?->format('Y-m-d H:i:s') }}
                            </div>
                        </div>

                        @if($heartbeatAutoRefresh)
                            <div class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-800">
                                <span class="h-3 w-3 animate-spin rounded-full border-2 border-blue-200 border-t-blue-700"></span>
                                Auto-refreshing
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="rounded-2xl border {{ $allOk ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm font-semibold {{ $allOk ? 'text-emerald-800' : 'text-amber-800' }}">
                        {{ $allOk ? 'All required checks passed.' : 'Some checks need attention.' }}
                    </div>

                    <form method="POST" action="{{ route('template.admin.system-check.heartbeat') }}">
                        @csrf

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-100"
                        >
                            Run queue heartbeat test
                        </button>
                    </form>
                </div>

                @unless($allOk)
                    <div class="mt-3 space-y-3">
                        <div class="rounded-xl border border-amber-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                                Required fixes
                            </div>

                            <div class="mt-3 space-y-3">
                                @foreach($failedChecks as $failedCheck)
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $failedCheck['label'] }}
                                        </div>

                                        <div class="mt-1 text-xs text-gray-600">
                                            {{ $failedCheck['detail'] }}
                                        </div>

                                        <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-900 px-4 py-3 text-xs text-white">{{ $failedCheck['fix'] }}</pre>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endunless
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Check</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Detail</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Fix</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @foreach($checks as $check)
                            <tr>
                                <td class="px-4 py-3">
                                    @if($check['ok'])
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">OK</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Missing</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $check['label'] }}
                                </td>

                                <td class="px-4 py-3 text-gray-600">
                                    {{ $check['detail'] }}
                                </td>

                                <td class="px-4 py-3 text-gray-600">
                                    {{ $check['ok'] ? '—' : $check['fix'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($heartbeatAutoRefresh)
        <script>
            window.setTimeout(() => {
                window.location.reload();
            }, 2000);
        </script>
    @endif
</x-app-layout>
