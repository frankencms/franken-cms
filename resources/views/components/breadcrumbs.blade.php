@if (! empty($breadcrumbs))
    <nav aria-label="Breadcrumbs" {{ $attributes->merge(['class' => 'breadcrumbs']) }}>
        <ol class="flex items-center space-x-2" itemscope itemtype="https://schema.org/BreadcrumbList">
            @foreach ($breadcrumbs as $index => $breadcrumb)
                <li
                    class="flex items-center"
                    itemprop="itemListElement"
                    itemscope
                    itemtype="https://schema.org/ListItem"
                >
                    @if ($breadcrumb->url && ! $loop->last)
                        <a href="{{ $breadcrumb->url }}" class="transition-colors hover:text-lime-400" itemprop="item">
                            <span itemprop="name">{{ $breadcrumb->title }}</span>
                        </a>
                        <meta itemprop="position" content="{{ $index + 1 }}" />
                        @if (! $loop->last)
                            <svg
                                class="mx-2 h-4 w-4 text-emerald-400/60"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        @endif
                    @else
                        <span class="font-medium text-lime-300" itemprop="name">{{ $breadcrumb->title }}</span>
                        <meta itemprop="position" content="{{ $index + 1 }}" />
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
