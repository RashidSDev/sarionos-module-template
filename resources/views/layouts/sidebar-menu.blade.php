@php
    $mainMenu = [
        ['name' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
    ];

    $roleId  = session('sarionos_role_id');
    $isAdmin = in_array($roleId, [1, 2], true);
    $isOwner = (bool) session('sarionos_is_workspace_owner', false);

    $navigationItems = collect(session('sarionos_navigation_items', []));
    $hasDynamicNavigation = $navigationItems->isNotEmpty();
    $navigationGroups = $navigationItems->groupBy(fn ($item) => $item['group_label'] ?? 'Navigation');
@endphp

@if ($hasDynamicNavigation)
    @foreach ($navigationGroups as $groupLabel => $items)
        <div class="{{ $loop->first ? 'space-y-2' : 'so-sidebar-section' }}">
            @unless($loop->first)
                <p
                    class="so-sidebar-section-title"
                    x-show="open"
                >
                    {{ $groupLabel }}
                </p>
            @endunless

            @foreach ($items as $item)
                @php
                    $url = (string) ($item['url'] ?? '#');
                    $href = \Illuminate\Support\Str::startsWith($url, ['http://', 'https://'])
                        ? $url
                        : url($url);

                    $icon = $item['icon'] ?: 'squares-2x2';
                    $iconView = 'so::components.icons.' . $icon;

                    if (! view()->exists($iconView)) {
                        $iconView = 'so::components.icons.squares-2x2';
                    }
                @endphp

                <a
                    href="{{ $href }}"
                    class="so-sidebar-link"
                    :class="open ? 'gap-3' : 'gap-0 justify-center'"
                >
                    @component($iconView, ['class' => 'so-sidebar-link-icon'])
                    @endcomponent

                    <span
                        class="so-sidebar-link-label whitespace-nowrap"
                        x-show="open"
                        x-transition.opacity.duration.150ms
                    >
                        {{ $item['label'] ?? 'Untitled' }}
                    </span>
                </a>
            @endforeach
        </div>
    @endforeach
@else
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
@endif

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
            href="{{ rtrim(config('sarionos.web_url'), '/') }}/dashboard?refresh_context=1"
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