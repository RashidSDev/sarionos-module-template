<x-so-layout :title="$title ?? ''">

   <x-slot name="assets">
        @vite(['resources/css/app.css', 'resources/js/app.js'])


    </x-slot>

    <x-slot name="menu">
        @include('layouts.sidebar-menu')
    </x-slot>

    {{ $slot }}

</x-so-layout>
