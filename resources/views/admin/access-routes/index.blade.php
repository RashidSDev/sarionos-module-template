<x-app-layout title="Access Route Registry">
    <div class="space-y-4">
        <div class="flex items-start justify-between gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div>
                <h1 class="text-lg font-semibold text-gray-900">Access Route Registry</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Local Module Template routes are compared with Core registered access route rules.
                </p>
            </div>

            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                Back to dashboard
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('template.admin.access-routes.register') }}" class="rounded-xl border border-gray-200 bg-white shadow-sm">
            @csrf

            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 px-4 py-3">
                <div>
                    <div class="text-sm font-semibold text-gray-900">App key: {{ $appKey }}</div>
                    <div class="text-xs text-gray-500">
                        Local routes: {{ count($localRoutes) }} · Core rules: {{ count($coreRules) }}
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <button type="button" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50" data-action="access-routes-select-all">
                        Select all
                    </button>

                    <button type="button" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50" data-action="access-routes-select-none">
                        Unselect all
                    </button>

                    <button type="button" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100" data-action="access-routes-select-not-sent">
                        Select not sent
                    </button>

                    <button type="submit" class="rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white">
                        Register selected
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1180px] divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-3 py-2">Send</th>
                            <th class="px-3 py-2">Method</th>
                            <th class="px-3 py-2">URI</th>
                            <th class="px-3 py-2">Route name</th>
                            <th class="px-3 py-2">Match</th>
                            <th class="w-32 px-3 py-2">Core</th>
                            <th class="w-28 px-3 py-2">Protected</th>
                            <th class="w-44 px-3 py-2">Access key</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($localRoutes as $route)
                            @php
                                $coreKey = strtoupper($route['method']) . ' ' . trim($route['uri'], '/');
                                $coreRule = $coreByKey->get($coreKey);
                                $isSent = (bool) $coreRule;
                                $isActive = (bool) ($coreRule['is_active'] ?? false);
                                $isProtected = (bool) ($coreRule['is_protected'] ?? false);
                            @endphp

                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2">
                                    <input
                                        type="checkbox"
                                        name="routes[]"
                                        value='@json($route)'
                                        class="rounded border-gray-300"
                                        data-access-route-send-checkbox
                                        data-core-sent="{{ $isSent ? '1' : '0' }}"
                                        @checked(! $isSent)
                                    >
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-gray-700">{{ $route['method'] }}</td>
                                <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-gray-900">{{ $route['uri'] }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-xs text-gray-600">{{ $route['route_name'] ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-gray-600">{{ $route['match_value'] }}</td>
                                <td class="whitespace-nowrap px-3 py-2">
                                    @if ($isSent)
                                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                                            Sent{{ $isActive ? ' / active' : ' / inactive' }}
                                        </span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">
                                            Not sent
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-2">
                                    @if ($isProtected)
                                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">Protected</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">Open</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-gray-600">
                                    {{ $coreRule['access_key'] ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            const action = event.target.closest('[data-action]');
            if (! action) return;

            const checkboxes = document.querySelectorAll('[data-access-route-send-checkbox]');

            if (action.matches('[data-action="access-routes-select-all"]')) {
                checkboxes.forEach((checkbox) => checkbox.checked = true);
            }

            if (action.matches('[data-action="access-routes-select-none"]')) {
                checkboxes.forEach((checkbox) => checkbox.checked = false);
            }

            if (action.matches('[data-action="access-routes-select-not-sent"]')) {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = checkbox.dataset.coreSent !== '1';
                });
            }
        });
    </script>
</x-app-layout>
