@php
    $mainMenu = [
        ['name' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
    ];

    $roleId  = session('sarionos_role_id');
    $isAdmin = in_array($roleId, [1, 2], true);
    $isOwner = (bool) session('sarionos_is_workspace_owner', false);
@endphp

<div class="space-y-2">
    @foreach ($mainMenu as $item)
        <a
            href="{{ route($item['route']) }}"
            class="so-sidebar-link"
            :class="open ? 'gap-3' : 'gap-0 justify-center'"
        >
            @component('so::components.icons.' . $item['icon'], ['class' => 'so-sidebar-link-icon'])
            @endcomponent

            <span
                class="so-sidebar-link-label whitespace-nowrap"
                x-show="open"
                x-transition.opacity.duration.150ms
            >
                {{ $item['name'] }}
            </span>
        </a>
    @endforeach
</div>

@if ($isOwner)
    <div class="so-sidebar-section">
        <p
            class="so-sidebar-section-title"
            x-show="open"
        >
            Administration
        </p>

        <a
            href="#"
            class="so-sidebar-link-disabled"
            :class="open ? 'gap-3' : 'gap-0 justify-center'"
        >
            @component('so::components.icons.settings', ['class' => 'so-sidebar-link-icon so-sidebar-link-icon-disabled'])
            @endcomponent

            <span
                class="so-sidebar-link-label"
                x-show="open"
                x-transition.opacity.duration.150ms
            >
                Settings
            </span>
        </a>
    </div>
@endif

@if ($isAdmin)
    <div class="so-sidebar-section">
        <p
            class="so-sidebar-section-title"
            x-show="open"
        >
            Hub
        </p>

        <a
            href="https://app.dev.sarionos.com/dashboard?refresh_context=1"
            class="so-sidebar-feature-link"
            :class="open ? 'gap-3' : 'gap-0 justify-center'"
        >
            <x-so::icons.layout-dashboard class="w-6 h-6 text-gray-600 shrink-0" />

            <div
                x-show="open"
                x-transition.opacity.duration.150ms
                class="so-sidebar-feature-body"
            >
                <span class="so-sidebar-feature-title">
                    Workspace Hub
                </span>
                <div class="so-sidebar-feature-meta">
                    <div>Main workspace</div>
                    <div>surface</div>
                </div>
            </div>
        </a>
    </div>
@endif