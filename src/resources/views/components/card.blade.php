<div {{ $attributes->merge(['class'=>'card']) }}>
  @isset($header)
    <div class="card-header">{{ $header }}</div>
  @endisset
  <div class="card-body">
    {{ $slot }}
  </div>
  @isset($footer)
    <div class="px-5 pb-5">{{ $footer }}</div>
  @endisset
</div>
