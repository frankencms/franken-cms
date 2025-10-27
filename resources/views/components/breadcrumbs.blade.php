@if (! empty($breadcrumbs))
    <nav aria-label="Breadcrumbs" {{ $attributes->merge(['class' => 'breadcrumbs']) }}>
        <ol class="flex items-center space-x-2">
            @foreach ($breadcrumbs as $breadcrumb)
                <li class="flex items-center">
                    @if ($breadcrumb->url && ! $loop->last)
                        <a href="{{ $breadcrumb->url }}" class="transition-colors hover:text-lime-400">
                            {{ $breadcrumb->title }}
                        </a>
                        <svg
                            class="mx-2 h-4 w-4 text-emerald-400/60"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    @else
                        <span class="font-medium text-lime-300" aria-current="page">{{ $breadcrumb->title }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
