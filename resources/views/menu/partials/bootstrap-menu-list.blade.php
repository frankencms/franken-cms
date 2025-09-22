{{-- Bootstrap Menu List Partial --}}
<ul class="navbar-nav">
    @foreach($items as $item)
        @if($item['is_active'])
            @if(!empty($item['children']))
                <li class="nav-item dropdown {{ $item['css_class'] ?? '' }}">
                    <a class="nav-link dropdown-toggle"
                       href="{{ $item['url'] }}"
                       target="{{ $item['target'] ?? '_self' }}"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false"
                       @if(!empty($item['additional_data']['title'])) title="{{ $item['additional_data']['title'] }}" @endif>
                        @if(!empty($item['icon']))
                            @if(str_starts_with($item['icon'], 'heroicon-'))
                                <x-dynamic-component :component="$item['icon']" class="w-4 h-4" />
                            @else
                                <i class="{{ $item['icon'] }}"></i>
                            @endif
                        @endif
                        {{ $item['label'] }}
                    </a>
                    <ul class="dropdown-menu">
                        @foreach($item['children'] as $child)
                            @if($child['is_active'])
                                <li>
                                    <a class="dropdown-item {{ $child['css_class'] ?? '' }}"
                                       href="{{ $child['url'] }}"
                                       target="{{ $child['target'] ?? '_self' }}"
                                       @if(!empty($child['additional_data']['title'])) title="{{ $child['additional_data']['title'] }}" @endif>
                                        @if(!empty($child['icon']))
                                            @if(str_starts_with($child['icon'], 'heroicon-'))
                                                <x-dynamic-component :component="$child['icon']" class="w-4 h-4" />
                                            @else
                                                <i class="{{ $child['icon'] }}"></i>
                                            @endif
                                        @endif
                                        {{ $child['label'] }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>
            @else
                <li class="nav-item {{ $item['css_class'] ?? '' }}">
                    <a class="nav-link"
                       href="{{ $item['url'] }}"
                       target="{{ $item['target'] ?? '_self' }}"
                       @if(!empty($item['additional_data']['title'])) title="{{ $item['additional_data']['title'] }}" @endif>
                        @if(!empty($item['icon']))
                            @if(str_starts_with($item['icon'], 'heroicon-'))
                                <x-dynamic-component :component="$item['icon']" class="w-4 h-4" />
                            @else
                                <i class="{{ $item['icon'] }}"></i>
                            @endif
                        @endif
                        {{ $item['label'] }}
                    </a>
                </li>
            @endif
        @endif
    @endforeach
</ul>