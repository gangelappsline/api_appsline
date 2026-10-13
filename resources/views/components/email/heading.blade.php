@props([
    'level' => 'h1',          // h1 | h2
])

@php($class = $level === 'h2'
    ? 'font-size:19px; line-height:27px; font-weight:700; color:'.config('appsline.colors.ink').';'
    : 'font-size:27px; line-height:35px; font-weight:700; letter-spacing:-.6px; color:'.config('appsline.colors.ink').';')

<{{ $level === 'h2' ? 'h2' : 'h1' }} class="{{ $level }}" {{ $attributes->merge(['style' => 'margin:0 0 14px 0; font-family:'.config('appsline.font').'; '.$class]) }}>
    {{ $slot }}
</{{ $level === 'h2' ? 'h2' : 'h1' }}>
