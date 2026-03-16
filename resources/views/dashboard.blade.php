<x-so-layout title="Module Template Dashboard">
    <x-slot name="assets">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </x-slot>

    <x-slot name="menu">
        @include('layouts.sidebar-menu')
    </x-slot>

    <div class="space-y-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Module Template Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600">Starter module using the shared SarionOS layout.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Session</h2>

                <div class="space-y-2 text-sm text-gray-700">
                    <p><strong>User:</strong> {{ session('sarionos_user_name') }}</p>
                    <p><strong>User UUID:</strong> {{ session('sarionos_user_uuid') }}</p>
                    <p><strong>Role ID:</strong> {{ session('sarionos_role_id') }}</p>
                    <p><strong>Workspace:</strong> {{ session('sarionos_active_workspace_name') }}</p>
                    <p><strong>Workspace UUID:</strong> {{ session('sarionos_active_workspace_uuid') }}</p>
                    <p><strong>Workspace Owner:</strong> {{ session('sarionos_is_workspace_owner') ? 'Yes' : 'No' }}</p>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Starter Status</h2>

                <div class="space-y-2 text-sm text-gray-700">
                    <p>Shared layout is active.</p>
                    <p>Core SSO is active.</p>
                    <p>Workspace context is loaded.</p>
                    <p>This module is ready to become the base starter for future modules.</p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Modules loaded</h2>
            <pre class="text-xs text-gray-700 overflow-auto">{{ json_encode(session('sarionos_workspace_modules', []), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>

        <div class="bg-white shadow rounded-lg p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Workspace users loaded</h2>
            <pre class="text-xs text-gray-700 overflow-auto">{{ json_encode(session('sarionos_workspace_users', []), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>

        <div class="bg-white shadow rounded-lg p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">User workspaces loaded</h2>
            <pre class="text-xs text-gray-700 overflow-auto">{{ json_encode(session('sarionos_user_workspaces', []), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>

        <div class="pt-2">
            <a href="{{ route('logout') }}" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                Logout
            </a>
        </div>
    </div>
</x-so-layout>