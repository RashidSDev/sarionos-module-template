@php
    $mainMenu = [
        ['name' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
    ];

    $modules = collect(session('sarionos_workspace_modules', []))
        ->filter(fn ($m) => isset($m['key'], $m['url']));

    $roleId  = session('sarionos_role_id');
    $isAdmin = in_array($roleId, [1, 2], true);
    $isOwner = (bool) session('sarionos_is_workspace_owner', false);
@endphp

<div class="space-y-2">
    @foreach ($mainMenu as $item)
        <a
            href="{{ route($item['route']) }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-200 transition"
        >
            @component('so::components.icons.' . $item['icon'], ['class' => 'w-5 h-5'])
            @endcomponent

            <span
                class="text-sm font-medium whitespace-nowrap"
                x-show="open"
                x-transition.opacity.duration.150ms
            >
                {{ $item['name'] }}
            </span>
        </a>
    @endforeach
</div>

{{-- @if ($modules->isNotEmpty())
    <div class="mt-6 pt-4 border-t border-gray-200">
        <p
            class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wide"
            x-show="open"
        >
            Modules
        </p>

        <div class="space-y-2">
            @foreach ($modules as $module)
                <a
                    href="{{ $module['url'] }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-200 transition"
                >
                    @component('so::components.icons.squares-2x2', ['class' => 'w-5 h-5 text-gray-500'])
                    @endcomponent

                    <span
                        class="text-sm font-medium capitalize"
                        x-show="open"
                        x-transition.opacity.duration.150ms
                    >
                        {{ $module['key'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@endif --}}

{{-- ========================================================= --}}
{{-- WORKSPACE ADMINISTRATION --}}
{{-- ========================================================= --}}
@if($isOwner)
    <div class="mt-6 pt-4 border-t border-gray-200">
        <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wide"
           x-show="open">
            ADMINISTRATION
        </p>        

        {{-- Add Settings Section for Admin/Owner --}}
        <a href="" 
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-200 transition">
            @component('so::components.icons.settings', ['class' => 'w-5 h-5 text-gray-500'])
            @endcomponent

            <span class="text-sm font-medium"
                  x-show="open"
                  x-transition.opacity.duration.150ms>
                Settings
            </span>
        </a>
    </div>
@endif

@if ($isAdmin)
    <div class="mt-6 pt-4 border-t border-gray-200">
        <p
            class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wide"
            x-show="open"
        >
            System
        </p>

            <a href="https://app.dev.sarionos.com/dashboard?refresh_context=1"
            class="flex items-center p-3 rounded-lg
                    text-gray-800 hover:bg-gray-100 transition"
            :class="open ? 'gap-3' : 'gap-0'">

                {{-- ICON (sarionos-ui) --}}
                <x-so::icons.layout-dashboard class="w-6 h-6 text-gray-600" />

                {{-- LABEL --}}
                <div x-show="open"
                    x-transition.opacity.duration.150ms
                    class="flex flex-col leading-tight">
                    <span class="text-sm font-semibold">
                        SarionOS Web
                    </span>
                    <div class="text-xs text-gray-400 leading-tight">
                        <div>Main operational</div>
                        <div>surface</div>
                    </div>
                </div>
            </a>

        </div>
  
@endif