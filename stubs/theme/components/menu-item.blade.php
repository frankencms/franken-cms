@props(['item'])

@php
    // Smart URL handling - use url() for relative, direct output for absolute
    if (str_starts_with($item['url'], 'http://') || str_starts_with($item['url'], 'https://')) {
        $href = $item['url'];
    } else {
        $href = url($item['url']);
    }
@endphp

<a 
    href="{{ $href }}"
    @if(($item['target'] ?? '_self') !== '_self')
        target="{{ $item['target'] }}"
    @endif
    @if(($item['target'] ?? '_self') === '_blank')
        rel="noopener noreferrer"
    @endif
    {{ $attributes }}
>
    {{ $item['label'] }}
</a>
