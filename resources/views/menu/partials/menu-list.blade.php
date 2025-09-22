{{-- Recursive Menu List Partial --}}
<ul class="menu__list">
    @foreach($items as $item)
        @if($item['is_active'])
            <li class="menu__item {{ $item['css_class'] ?? '' }}">
                <a href="{{ $item['url'] }}"
                   target="{{ $item['target'] ?? '_self' }}"
                   class="menu__link"
                   @if(!empty($item['additional_data']['title'])) title="{{ $item['additional_data']['title'] }}" @endif>
                    @if(!empty($item['icon']))
                        <span class="menu__icon">
                            @if(str_starts_with($item['icon'], 'heroicon-'))
                                <x-dynamic-component :component="$item['icon']" class="w-4 h-4" />
                            @else
                                <i class="{{ $item['icon'] }}"></i>
                            @endif
                        </span>
                    @endif
                    <span class="menu__label">{{ $item['label'] }}</span>
                </a>

                @if(!empty($item['children']))
                    <div class="menu__submenu">
                        @include('franken-cms::menu.partials.menu-list', ['items' => $item['children']])
                    </div>
                @endif
            </li>
        @endif
    @endforeach
</ul>