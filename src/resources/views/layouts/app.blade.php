<!doctype html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Platform4MMC') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    {{-- Include the nav ONCE --}}
    @includeIf('layouts.navigation')

    @if(!empty($header))
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h2 class="text-xl font-semibold">{{ $header }}</h2>
            </div>
        </header>
    @endif

    <main class="max-w-7xl mx-auto px-4 py-6">
        {{ $slot }} {{-- Livewire page content renders here --}}
    </main>

    @livewireScripts
</body>
</html>
