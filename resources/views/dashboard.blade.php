@php
    $moduleScope = (string) session('sarionos_module_scope', '');

    $isWorkspaceScope = $moduleScope === 'workspace';
    $isPersonalScope = $moduleScope === 'personal';
    $isSystemScope = $moduleScope === 'system';

    $scopeLabel = match ($moduleScope) {
        'workspace' => 'Workspace',
        'personal' => 'Personal',
        'system' => 'System',
        default => 'Unknown',
    };

    $workspaceModules = $isWorkspaceScope
        ? collect(session('sarionos_workspace_modules', []))
        : collect();

    $workspaceUsers = $isWorkspaceScope
        ? collect(session('sarionos_workspace_users', []))
        : collect();

    $userWorkspaces = $isWorkspaceScope
        ? collect(session('sarionos_user_workspaces', []))
        : collect();

    $currentModule = collect(session('sarionos_modules', []))
        ->first(
            fn ($module) =>
                is_array($module)
                && ($module['key'] ?? null)
                    === config('sarionos.module_key')
        );

    $sessionStatus = session('sarionos_token')
        ? 'Loaded'
        : 'Missing';
@endphp

<x-app-layout title="{{ config('sarionos.module_name', 'Module') }} Dashboard">
    <x-slot name="assets">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </x-slot>

    <x-slot name="menu">
        @include('layouts.sidebar-menu')
    </x-slot>

    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    {{ config('sarionos.module_name', 'Module') }}
                </h1>

                <p class="mt-1 text-sm text-gray-600">
                    Generic module runtime driven by the scope resolved by SarionOS Core.
                </p>
            </div>

            <a
                href="{{ route('template.admin.access-routes.index') }}"
                class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800"
            >
                Access Routes
            </a>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <section class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Core Scope
                </div>

                <div class="mt-1 text-xl font-bold text-gray-900">
                    {{ $scopeLabel }}
                </div>

                <div class="mt-1 text-xs text-gray-500">
                    Resolved from Core registration.
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    User
                </div>

                <div class="mt-1 truncate text-base font-bold text-gray-900">
                    {{ session('sarionos_user_name') ?? '—' }}
                </div>

                <div class="mt-1 text-xs text-gray-500">
                    Authenticated Core identity.
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Role
                </div>

                <div class="mt-1 text-xl font-bold text-gray-900">
                    {{ session('sarionos_role_id') ?? '—' }}
                </div>

                <div class="mt-1 text-xs text-gray-500">
                    Core role ID.
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    SSO
                </div>

                <div class="mt-1 text-xl font-bold {{ $sessionStatus === 'Loaded' ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ $sessionStatus }}
                </div>

                <div class="mt-1 text-xs text-gray-500">
                    Shared authentication session.
                </div>
            </section>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-gray-900">
                        Ownership Context
                    </h2>

                    <p class="mt-1 text-xs text-gray-500">
                        The boundary selected from the Core-resolved application scope.
                    </p>
                </div>

                <div class="space-y-3 px-4 py-4 text-sm">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                            User UUID
                        </div>

                        <div class="mt-1 break-all font-mono text-xs text-gray-700">
                            {{ session('sarionos_user_uuid') ?? '—' }}
                        </div>
                    </div>

                    @if($isWorkspaceScope)
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                                Workspace
                            </div>

                            <div class="mt-1 font-semibold text-gray-900">
                                {{ session('sarionos_active_workspace_name') ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                                Workspace UUID
                            </div>

                            <div class="mt-1 break-all font-mono text-xs text-gray-700">
                                {{ session('sarionos_active_workspace_uuid') ?? '—' }}
                            </div>
                        </div>

                        <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-xs text-blue-800">
                            Workspace scope uses the active Workspace UUID as its generic ownership/context boundary.
                        </div>
                    @elseif($isPersonalScope)
                        <div class="rounded-lg border border-violet-100 bg-violet-50 p-3 text-xs text-violet-800">
                            Personal scope uses the authenticated User UUID as its generic ownership boundary. No active workspace is required.
                        </div>
                    @elseif($isSystemScope)
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700">
                            System scope has no generic workspace boundary. Authenticated user context is available for application-specific authorization.
                        </div>
                    @else
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs font-semibold text-red-700">
                            Core module scope is missing or invalid.
                        </div>
                    @endif
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-gray-900">
                        Runtime Contract
                    </h2>

                    <p class="mt-1 text-xs text-gray-500">
                        Generic behavior selected from Core rather than local configuration.
                    </p>
                </div>

                <div class="space-y-2 px-4 py-4 text-sm">
                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                        <span class="font-medium text-gray-700">
                            Scope authority
                        </span>

                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                            Core
                        </span>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                        <span class="font-medium text-gray-700">
                            Navigation
                        </span>

                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                            Core-driven
                        </span>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                        <span class="font-medium text-gray-700">
                            Workspace context
                        </span>

                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $isWorkspaceScope ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $isWorkspaceScope ? 'Required' : 'Not applicable' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                        <span class="font-medium text-gray-700">
                            Local scope setting
                        </span>

                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">
                            None
                        </span>
                    </div>
                </div>
            </section>
        </div>

        @if($isWorkspaceScope)
            <section class="rounded-xl border border-blue-200 bg-white shadow-sm">
                <div class="border-b border-blue-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-blue-900">
                        Workspace Context
                    </h2>

                    <p class="mt-1 text-xs text-gray-500">
                        Loaded only because Core resolved this application as Workspace scope.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 p-4 md:grid-cols-3">
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="text-xl font-bold text-gray-900">
                            {{ $workspaceModules->count() }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Workspace applications
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="text-xl font-bold text-gray-900">
                            {{ $workspaceUsers->count() }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Workspace users
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="text-xl font-bold text-gray-900">
                            {{ $userWorkspaces->count() }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Available workspaces
                        </div>
                    </div>
                </div>

                @if($userWorkspaces->count() > 1)
                    <div class="border-t border-blue-100 p-4">
                        <form
                            method="POST"
                            action="{{ route('workspace.switch') }}"
                            class="flex flex-col gap-2 sm:flex-row sm:items-end"
                        >
                            @csrf

                            <label class="flex-1">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Switch workspace
                                </span>

                                <select
                                    name="workspace_uuid"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm"
                                    required
                                >
                                    @foreach($userWorkspaces as $workspace)
                                        <option
                                            value="{{ $workspace['uuid'] ?? '' }}"
                                            @selected(
                                                ($workspace['uuid'] ?? null)
                                                === session('sarionos_active_workspace_uuid')
                                            )
                                        >
                                            {{ $workspace['name'] ?? ($workspace['uuid'] ?? 'Workspace') }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <button
                                type="submit"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                            >
                                Switch
                            </button>
                        </form>
                    </div>
                @endif
            </section>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <h2 class="text-sm font-semibold text-gray-900">
                            Workspace Users
                        </h2>
                    </div>

                    <pre class="max-h-80 overflow-auto p-4 text-xs text-gray-700">{{ json_encode($workspaceUsers->values()->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <h2 class="text-sm font-semibold text-gray-900">
                            User Workspaces
                        </h2>
                    </div>

                    <pre class="max-h-80 overflow-auto p-4 text-xs text-gray-700">{{ json_encode($userWorkspaces->values()->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <h2 class="text-sm font-semibold text-gray-900">
                            Workspace Applications
                        </h2>
                    </div>

                    <pre class="max-h-80 overflow-auto p-4 text-xs text-gray-700">{{ json_encode($workspaceModules->values()->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </section>
            </div>
        @else
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-gray-900">
                        Core Module Context
                    </h2>

                    <p class="mt-1 text-xs text-gray-500">
                        No workspace catalogue is consumed for {{ strtolower($scopeLabel) }} scope.
                    </p>
                </div>

                <pre class="max-h-80 overflow-auto p-4 text-xs text-gray-700">{{ json_encode($currentModule, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </section>
        @endif
    </div>
</x-app-layout>
