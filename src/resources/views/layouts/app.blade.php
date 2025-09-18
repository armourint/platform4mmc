<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        @hasSection('title')
            @yield('title') — {{ config('app.name', 'Platform4MMC') }}
        @else
            {{ config('app.name', 'Platform4MMC') }}
        @endif
    </title>

    @vite(['resources/css/app.css','resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <header class="border-b bg-white">
        <div class="mx-auto max-w-7xl px-4 py-3 flex items-center gap-6">
            <a href="{{ url('/') }}" class="font-semibold">{{ config('app.name', 'Platform4MMC') }}</a>
            <nav class="flex gap-4 text-sm">
                @auth
                    <a href="{{ route('projects.index') }}">Projects</a>
                    <a href="{{ route('assessments.index') }}">Assessments</a>
                    <a href="{{ route('knowledge.index') }}">Knowledge</a>
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('admin.datasets') }}">Admin</a>
                    @endif
                @endauth
            </nav>
            <div class="ml-auto text-sm">
                @auth
                    <span class="mr-2 text-gray-600">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button class="rounded border px-3 py-1">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded border px-3 py-1">Login</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8">
        @if (isset($slot))
            {{-- Livewire #[Layout('layouts.app')] / component-layout style --}}
            {{ $slot }}
        @else
            {{-- Classic Blade layout style --}}
            @yield('content')
        @endif
    </main>

    <footer class="mt-16 border-t bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 text-sm text-gray-600">
            © {{ date('Y') }} {{ config('app.name', 'Platform4MMC') }}
        </div>
    </footer>

    @livewireScripts
</body>
</html>
