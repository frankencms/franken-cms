@props(['user'])

<div {{ $attributes->merge(['class' => 'author-bio rounded-lg border border-emerald-700/30 bg-emerald-950/20 p-6']) }}>
    <div class="flex flex-col gap-4 md:flex-row md:items-start">
        {{-- Author Avatar --}}
        <div class="shrink-0">
            @if ($user->bio && $user->bio->hasMedia('bio-image'))
                @php
                    $bioImage = $user->bio->getFirstMedia('bio-image');
                    $bioImageUrl = $bioImage->hasGeneratedConversion('bio-thumb')
                        ? $bioImage->getUrl('bio-thumb')
                        : $bioImage->getUrl();
                    $bioImageAlt = $bioImage->getCustomProperty('alt') ?? $user->name;
                @endphp
                <img
                    src="{{ $bioImageUrl }}"
                    alt="{{ $bioImageAlt }}"
                    class="size-16 rounded-full object-cover ring-2 ring-emerald-700/30"
                    loading="lazy"
                />
            @else
                <div class="flex size-16 items-center justify-center rounded-full bg-emerald-900/50 text-2xl font-bold text-lime-300 ring-2 ring-emerald-700/30">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
        </div>

        {{-- Author Info --}}
        <div class="flex-1">
            <div class="mb-2">
                <h3 class="text-xl font-semibold text-lime-300">{{ $user->name }}</h3>
                @if ($user->bio->title)
                    <p class="text-sm text-emerald-300/80">{{ $user->bio->title }}</p>
                @endif
            </div>

            @if ($user->bio->bio)
                <div class="mb-4 text-emerald-100/90">
                    {{ $user->bio->bio }}
                </div>
            @endif

            {{-- Website and Social Links --}}
            <div class="flex flex-wrap items-center gap-4">
                @if ($user->bio->website)
                    <a
                        href="{{ $user->bio->website }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 text-sm text-lime-400 transition-colors hover:text-lime-300"
                    >
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
                            />
                        </svg>
                        Website
                    </a>
                @endif

                @if ($user->bio->social_links && count($user->bio->social_links) > 0)
                    @foreach ($user->bio->social_links as $platform => $url)
                        <a
                            href="{{ $url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 text-sm text-lime-400 transition-colors hover:text-lime-300"
                            aria-label="{{ ucfirst($platform) }}"
                        >
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                                />
                            </svg>
                            {{ ucfirst($platform) }}
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
