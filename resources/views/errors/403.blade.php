<x-app-layout title="Access denied">
    <div class="mx-auto flex min-h-[65vh] max-w-2xl items-center justify-center px-4 py-10">
        <div class="w-full rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-lg font-bold text-red-600">
                403
            </div>

            <h1 class="text-xl font-semibold text-gray-900">
                Access denied
            </h1>

            <p class="mt-2 text-sm text-gray-500">
                You do not have permission to open this Template area in the current workspace.
            </p>

            <div class="mt-6 flex flex-col justify-center gap-2 sm:flex-row">
                <button
                    type="button"
                    onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ route('dashboard') }}'"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                >
                    Go back
                </button>

                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-blue-600 bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    Go to dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
