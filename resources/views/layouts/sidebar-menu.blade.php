@include('so::components.sidebar-menu', [
    'currentModuleKey' => config('sarionos.module_key', 'template'),
    'fallbackMenu' => [
        ['name' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
    ],
])
