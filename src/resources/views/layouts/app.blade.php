<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $title ?? 'StopFloods' }}</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  @livewireStyles
  @stack('styles')
</head>
<body class="min-h-screen">
  {{-- Top bar --}}
  <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/80 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
        {{-- Swap the square + text for the logo --}}
        <img src="/images/logos/logo-uog.png" alt="University of Galway" class="h-16">
        </div>

        <div class="flex items-center gap-2">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn-ghost">Logout</button>
        </form>
        </div>
    </div>
    </header>


  <main class="mx-auto max-w-7xl px-4 py-6">
    @yield('content')
  </main>

  @livewireScripts
  @stack('scripts')
</body>
</html>
