@php
    $workspaceModules = collect(session('sarionos_workspace_modules', []));
    $workspaceUsers = collect(session('sarionos_workspace_users', []));
    $userWorkspaces = collect(session('sarionos_user_workspaces', []));

    $sessionStatus = session('sarionos_token') ? 'Loaded' : 'Missing';
    $workspaceStatus = session('sarionos_active_workspace_uuid') ? 'Loaded' : 'Missing';

    $moduleCount = $workspaceModules->count();
    $workspaceUserCount = $workspaceUsers->count();
    $userWorkspaceCount = $userWorkspaces->count();
@endphp

<x-app-layout title="Module Template Dashboard">
    <x-slot name="assets">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </x-slot>

    <x-slot name="menu">
        @include('layouts.sidebar-menu')
    </x-slot>

    <div class="space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Module Template Dashboard</h1>
                <p class="mt-1 text-sm text-gray-600">Starter operational dashboard using the shared SarionOS layout.</p>
            </div>

            <a href="{{ route('template.admin.access-routes.index') }}"
               class="rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800">
                Access Routes
            </a>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-xl border border-gray-200 border-t-2 border-t-blue-500 bg-white px-4 py-3 shadow-sm">
                <div class="text-xl font-bold text-blue-600">{{ $moduleCount }}</div>
                <div class="mt-1 text-xs font-bold uppercase tracking-wide text-gray-800">Modules</div>
                <div class="text-[11px] text-gray-500">Workspace apps loaded</div>
            </div>

            <div class="rounded-xl border border-gray-200 border-t-2 border-t-green-500 bg-white px-4 py-3 shadow-sm">
                <div class="text-xl font-bold text-green-600">{{ $workspaceUserCount }}</div>
                <div class="mt-1 text-xs font-bold uppercase tracking-wide text-gray-800">Users</div>
                <div class="text-[11px] text-gray-500">Workspace users loaded</div>
            </div>

            <div class="rounded-xl border border-gray-200 border-t-2 border-t-purple-500 bg-white px-4 py-3 shadow-sm">
                <div class="text-xl font-bold text-purple-600">{{ $userWorkspaceCount }}</div>
                <div class="mt-1 text-xs font-bold uppercase tracking-wide text-gray-800">Workspaces</div>
                <div class="text-[11px] text-gray-500">Available to this user</div>
            </div>

            <div class="rounded-xl border border-gray-200 border-t-2 border-t-amber-500 bg-white px-4 py-3 shadow-sm">
                <div class="text-xl font-bold text-amber-600">{{ session('sarionos_role_id') ?? '—' }}</div>
                <div class="mt-1 text-xs font-bold uppercase tracking-wide text-gray-800">Role</div>
                <div class="text-[11px] text-gray-500">Core role ID</div>
            </div>

            <div class="rounded-xl border border-gray-200 border-t-2 border-t-red-500 bg-white px-4 py-3 shadow-sm">
                <div class="text-xl font-bold text-red-600">0</div>
                <div class="mt-1 text-xs font-bold uppercase tracking-wide text-gray-800">Warnings</div>
                <div class="text-[11px] text-gray-500">Dummy health state</div>
            </div>

            <div class="rounded-xl border border-gray-200 border-t-2 border-t-gray-500 bg-white px-4 py-3 shadow-sm">
                <div class="text-xl font-bold text-gray-700">{{ session('sarionos_is_workspace_owner') ? 'Yes' : 'No' }}</div>
                <div class="mt-1 text-xs font-bold uppercase tracking-wide text-gray-800">Owner</div>
                <div class="text-[11px] text-gray-500">Workspace ownership</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 xl:grid-cols-3">
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-gray-900">Session Context</h2>
                    <p class="text-xs text-gray-500">Current authenticated module session.</p>
                </div>

                <div class="space-y-2 px-4 py-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-gray-500">User</span>
                        <span class="font-semibold text-gray-900">{{ session('sarionos_user_name') ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-gray-500">Role ID</span>
                        <span class="font-semibold text-gray-900">{{ session('sarionos_role_id') ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-gray-500">Workspace</span>
                        <span class="font-semibold text-gray-900">{{ session('sarionos_active_workspace_name') ?? '—' }}</span>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-2">
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">User UUID</div>
                        <div class="break-all font-mono text-xs text-gray-700">{{ session('sarionos_user_uuid') ?? '—' }}</div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-2">
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Workspace UUID</div>
                        <div class="break-all font-mono text-xs text-gray-700">{{ session('sarionos_active_workspace_uuid') ?? '—' }}</div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-gray-900">Starter Status</h2>
                    <p class="text-xs text-gray-500">Baseline checks for future modules.</p>
                </div>

                <div class="space-y-2 px-4 py-3">
                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm">
                        <span class="font-medium text-gray-700">Shared layout</span>
                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">Active</span>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm">
                        <span class="font-medium text-gray-700">Core SSO</span>
                        <span class="rounded-full {{ $sessionStatus === 'Loaded' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }} px-2 py-0.5 text-xs font-semibold">
                            {{ $sessionStatus }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm">
                        <span class="font-medium text-gray-700">Workspace context</span>
                        <span class="rounded-full {{ $workspaceStatus === 'Loaded' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }} px-2 py-0.5 text-xs font-semibold">
                            {{ $workspaceStatus }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm">
                        <span class="font-medium text-gray-700">Dynamic sidebar</span>
                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">Core-driven</span>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-gray-900">Dummy Operations</h2>
                    <p class="text-xs text-gray-500">Placeholders for real future module data.</p>
                </div>

                <div class="space-y-2 px-4 py-3">
                    @foreach ([
                        ['label' => 'Starter resources', 'value' => 12, 'width' => 72],
                        ['label' => 'Pending actions', 'value' => 3, 'width' => 30],
                        ['label' => 'Warnings', 'value' => 0, 'width' => 6],
                    ] as $row)
                        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-800">{{ $row['label'] }}</span>
                                <span class="text-sm font-bold text-gray-900">{{ $row['value'] }}</span>
                            </div>
                            <div class="mt-1 h-1.5 rounded-full bg-gray-200">
                                <div class="h-1.5 rounded-full bg-blue-500" style="width: {{ $row['width'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="rounded-xl border border-blue-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-blue-100 px-4 py-3">
                <div>
                    <h2 class="text-sm font-semibold text-blue-800">Modules Loaded</h2>
                    <p class="text-xs text-gray-500">Apps received from Core workspace context.</p>
                </div>
                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">
                    {{ $moduleCount }} module(s)
                </span>
            </div>

            <div class="grid grid-cols-1 gap-2 p-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($workspaceModules as $module)
                    <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                        <div class="text-sm font-semibold text-gray-900">{{ $module['key'] ?? 'Module' }}</div>
                        <div class="mt-1 break-all text-[11px] text-gray-500">{{ $module['url'] ?? 'No URL' }}</div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4 text-sm text-gray-500">
                        No modules loaded.
                    </div>
                @endforelse
            </div>
        </section>

        <div class="grid grid-cols-1 gap-3 xl:grid-cols-3">
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-gray-900">Workspace Users Loaded</h2>
                </div>
                <pre class="max-h-80 overflow-auto p-4 text-xs text-gray-700">{{ json_encode(session('sarionos_workspace_users', []), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-gray-900">User Workspaces Loaded</h2>
                </div>
                <pre class="max-h-80 overflow-auto p-4 text-xs text-gray-700">{{ json_encode(session('sarionos_user_workspaces', []), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-gray-900">Module Context JSON</h2>
                </div>
                <pre class="max-h-80 overflow-auto p-4 text-xs text-gray-700">{{ json_encode(session('sarionos_workspace_modules', []), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </section>
        </div>
    </div>
</x-app-layout>
