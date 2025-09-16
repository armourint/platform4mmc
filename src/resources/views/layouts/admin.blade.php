<!doctype html>
<html>
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>StopFloods</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
  <style> #map { height: 60vh; } </style>
</head>
<body>
  <aside>
    <nav>
      <a href="/dashboard">Dashboard</a> |
      <a href="/runs">Model Runs</a> |
      <a href="/upload">Upload & Import</a> |
      <a href="/tides">Tides & Sensors</a> |
      <a href="/scenarios">Scenarios</a> |
      <a href="/hindcasting">Hindcasting</a>
      

      <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">
  @csrf
</form>

<a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
   class="text-sm text-gray-600 hover:text-gray-900">
  Logout
</a>


    </nav>
  </aside>
  <main>@yield('content')</main>
  @livewireScripts
</body></html>
