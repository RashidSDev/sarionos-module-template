@php
    $mainMenu = [
        ['name' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
    ];

    $prefixes = ['dashboard'];
@endphp

<div id="so-apps-routes" class="hidden" data-prefixes='@json($prefixes)'></div>

<div class="space-y-2">
    @foreach ($mainMenu as $item)
        <a
            href="{{ route($item['route']) }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition"
            @click="closeMobile()"
        >
            @component('so::components.icons.' . $item['icon'], ['class' => 'w-5 h-5'])
            @endcomponent

            <span class="text-sm font-medium">{{ $item['name'] }}</span>
        </a>
    @endforeach
</div>