<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Platform4MMC — Admin</title>

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900">
  <header class="bg-white border-b">
    <nav class="max-w-7xl mx-auto px-6 py-3 flex items-center gap-4 text-sm">
      <a href="{{ route('projects.index') }}" class="font-semibold">Platform4MMC</a>

      <a href="{{ route('projects.index') }}"
         class="{{ request()->routeIs('projects.*') ? 'text-blue-600 font-medium' : 'text-gray-700 hover:text-gray-900' }}">
        Projects
      </a>

      <a href="{{ route('assessments.index') }}"
         class="{{ request()->routeIs('assessments.*') ? 'text-blue-600 font-medium' : 'text-gray-700 hover:text-gray-900' }}">
        Assessments
      </a>

      <a href="{{ route('knowledge.index') }}"
         class="{{ request()->routeIs('knowledge.*') ? 'text-blue-600 font-medium' : 'text-gray-700 hover:text-gray-900' }}">
        Knowledge
      </a>

      <a href="{{ route('admin.index') }}"
         class="{{ request()->routeIs('admin.*') ? 'text-blue-600 font-medium' : 'text-gray-700 hover:text-gray-900' }}">
        Admin
      </a>

      <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">@csrf</form>
      <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
         class="ml-auto text-gray-600 hover:text-gray-900">Logout</a>
    </nav>
  </header>

  <main class="max-w-7xl mx-auto px-6 py-8">
    {{-- Admin sub-nav --}}
    <div class="flex flex-wrap items-center gap-2 mb-6">
      <a href="{{ route('admin.index') }}"
         class="px-3 py-1.5 rounded-md text-sm font-medium border
                {{ request()->routeIs('admin.index') ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
        Overview
      </a>

      @if (Route::has('admin.layers'))
        <a href="{{ route('admin.layers') }}"
           class="px-3 py-1.5 rounded-md text-sm font-medium border
                  {{ request()->routeIs('admin.layers') ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
          Layers
        </a>
      @endif

      @if (Route::has('admin.rules'))
        <a href="{{ route('admin.rules') }}"
           class="px-3 py-1.5 rounded-md text-sm font-medium border
                  {{ request()->routeIs('admin.rules') ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
          Viability Rules
        </a>
      @endif

      @if (Route::has('admin.manufacturers'))
        <a href="{{ route('admin.manufacturers') }}"
           class="px-3 py-1.5 rounded-md text-sm font-medium border
                  {{ request()->routeIs('admin.manufacturers') ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
          Manufacturers
        </a>
      @endif

      @if (Route::has('admin.manufacturers.map'))
        <a href="{{ route('admin.manufacturers.map') }}"
           class="px-3 py-1.5 rounded-md text-sm font-medium border
                  {{ request()->routeIs('admin.manufacturers.map') ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
          Map
        </a>
      @endif

      @if (Route::has('admin.products'))
        <a href="{{ route('admin.products') }}"
           class="px-3 py-1.5 rounded-md text-sm font-medium border
                  {{ request()->routeIs('admin.products') ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
          EPD Products
        </a>
      @endif

      @if (Route::has('admin.imports'))
        <a href="{{ route('admin.imports') }}"
           class="px-3 py-1.5 rounded-md text-sm font-medium border
                  {{ request()->routeIs('admin.imports') ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
          Imports
        </a>
      @endif
    </div>

    {{-- Livewire pages render here when ->layout('admin') is used --}}
    {{ $slot ?? '' }}

    {{-- Blade pages render here when @extends('admin') + @section('content') are used --}}
    @yield('content')
  </main>

  @livewireScripts
  @stack('styles')
  @stack('scripts')
</body>
</html>
