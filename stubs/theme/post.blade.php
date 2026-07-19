{{-- Single Post Template --}}
<x-theme::layouts.main>
    <x-slot:main>
        <article class="bg-slate-950 py-12">
            <div class="container mx-auto px-4">
                {{-- Post Header --}}
                <header class="mx-auto mb-8 max-w-4xl">
                    {{-- Categories --}}
                    @if ($post->categories()->exists())
                        <div class="mb-4 flex flex-wrap gap-2">
                            @foreach ($post->categories as $category)
                                <a
                                    href="{{ $category->url }}"
                                    class="rounded-full bg-gradient-to-r from-lime-500/20 to-emerald-500/20 px-3 py-1 text-sm font-medium text-lime-300 ring-1 ring-lime-400/30 transition-all hover:from-lime-500/30 hover:to-emerald-500/30 hover:ring-lime-400/50"
                                >
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Post Title --}}
                    <h1
                        class="mb-4 bg-gradient-to-r from-lime-400 via-emerald-400 to-cyan-400 bg-clip-text text-4xl font-bold text-transparent drop-shadow-[0_0_20px_rgba(163,230,53,0.3)] md:text-5xl"
                    >
                        {{ $post->post_title }}
                    </h1>

                    {{-- Post Meta --}}
                    <div class="flex flex-wrap items-center gap-4 text-sm text-emerald-200/70">
                        @if ($post->author)
                            <div class="flex items-center gap-2">
                                <span>By</span>
                                <span class="font-medium text-lime-300">{{ $post->author->name }}</span>
                            </div>
                        @endif

                        @if ($post->post_published_at)
                            <div class="flex items-center gap-2">
                                <time datetime="{{ $post->post_published_at->toIso8601String() }}">
                                    {{ $post->post_published_at->format('F j, Y') }}
                                </time>
                            </div>
                        @endif

                        @if ($post->getMeta('read_time'))
                            <div class="flex items-center gap-2">
                                <span>{{ $post->getMeta('read_time') }} min read</span>
                            </div>
                        @endif
                    </div>

                    {{-- Post Teaser/Excerpt --}}
                    @if ($post->getMeta('post_teaser'))
                        <div class="mt-6 text-lg text-emerald-200/80">
                            {{ $post->getMeta('post_teaser') }}
                        </div>
                    @endif
                </header>

                {{-- Featured Image --}}
                @if ($post->hasMedia('featured'))
                    @php
                        $featuredMedia = $post->getFirstMedia('featured');
                        $cssClasses = $featuredMedia->getCustomProperty('css_classes', '');
                        $lazyLoading = $featuredMedia->getCustomProperty('lazy_loading', true);
                        $title = $featuredMedia->getCustomProperty('title');
                        $alt = $featuredMedia->getCustomProperty('alt');
                        $objectPosition = \FrankenCms\Support\FocalPoint::toCss($featuredMedia->getCustomProperty('focal_point'));

                        $attributes = [
                            'class' => trim('h-full w-full object-cover rounded shadow-[0_0_30px_rgba(163,230,53,0.15)] ring-1 ring-emerald-500/30 ' . $cssClasses),
                            'sizes' => '(max-width: 768px) 100vw, (max-width: 1280px) 896px, 1024px',
                            'loading' => $lazyLoading ? 'lazy' : 'eager',
                            'alt' => $alt,
                            'title' => $title,
                            'style' => $objectPosition,
                        ];
                    @endphp

                    <div class="mx-auto mb-12 max-w-4xl">
                        <div class="aspect-video overflow-hidden">
                            {!! $featuredMedia('featured', $attributes) !!}
                        </div>
                        @if ($featuredMedia->getCustomProperty('caption'))
                            <p class="mt-2 text-center text-sm text-emerald-200/60">
                                {{ $featuredMedia->getCustomProperty('caption') }}
                            </p>
                        @endif
                    </div>
                @endif

                {{-- Post Content --}}
                <div
                    class="mx-auto prose prose-lg max-w-4xl prose-invert prose-headings:bg-gradient-to-r prose-headings:from-lime-400 prose-headings:to-emerald-400 prose-headings:bg-clip-text prose-headings:text-transparent prose-p:text-emerald-200/80 prose-a:text-lime-400 prose-a:transition-colors hover:prose-a:text-cyan-400 prose-blockquote:border-l-lime-400 prose-blockquote:text-emerald-200/70 prose-strong:text-lime-300 prose-code:text-cyan-300"
                >
                    @if ($post->post_content)
                        {!! $post->renderRichContent('post_content') !!}
                    @endif
                </div>

                {{-- Tags --}}
                @if ($post->tags()->exists())
                    <div class="mx-auto mt-12 max-w-4xl">
                        <div class="border-t border-emerald-500/20 pt-6">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-emerald-200/70">Tags:</span>
                                @foreach ($post->tags as $tag)
                                    <a
                                        href="{{ $tag->url }}"
                                        class="rounded bg-gradient-to-r from-lime-500/10 to-emerald-500/10 px-3 py-1 text-sm text-lime-300 ring-1 ring-lime-400/20 transition-all hover:from-lime-500/20 hover:to-emerald-500/20 hover:ring-lime-400/40"
                                    >
                                        #{{ $tag->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Author Bio --}}
                @if ($post->author && $post->author->hasBio())
                    @php
                        $authorBio = $post->author->bio;
                        $bioImage = $authorBio->getFirstMedia('bio-image');
                        $bioImageShape = config('franken-cms.user_bio.image_shape', 'circle');
                    @endphp

                    <div class="mx-auto mt-12 max-w-4xl">
                        <div class="rounded-lg border border-emerald-700/30 bg-emerald-950/20 p-6">
                            <div class="flex flex-col gap-4 md:flex-row md:items-start">
                                {{-- Author Avatar --}}
                                <div class="shrink-0">
                                    @if ($bioImage)
                                        <img
                                            src="{{ $bioImage->hasGeneratedConversion('bio-thumb') ? $bioImage->getUrl('bio-thumb') : $bioImage->getUrl() }}"
                                            alt="{{ $bioImage->getCustomProperty('alt') ?? $post->author->name }}"
                                            class="{{ $bioImageShape === 'circle' ? 'rounded-full' : 'rounded-lg' }} size-16 object-cover ring-2 ring-emerald-700/30"
                                            loading="lazy"
                                        />
                                    @else
                                        <div
                                            class="{{ $bioImageShape === 'circle' ? 'rounded-full' : 'rounded-lg' }} flex size-16 items-center justify-center bg-emerald-900/50 text-2xl font-bold text-lime-300 ring-2 ring-emerald-700/30"
                                        >
                                            {{ strtoupper(substr($post->author->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Author Info --}}
                                <div class="flex-1">
                                    <div class="mb-2">
                                        <h3 class="text-xl font-semibold text-lime-300">{{ $post->author->name }}</h3>
                                        @if ($authorBio->title)
                                            <p class="text-sm text-emerald-300/80">{{ $authorBio->title }}</p>
                                        @endif
                                    </div>

                                    @if ($authorBio->bio)
                                        <div
                                            class="prose prose-sm mb-4 text-emerald-100/90 prose-p:text-emerald-100/90 prose-a:text-lime-400 hover:prose-a:text-cyan-400 prose-strong:text-lime-300"
                                        >
                                            {!! $authorBio->bio !!}
                                        </div>
                                    @endif

                                    {{-- Website and Social Links --}}
                                    <div class="flex flex-wrap items-center gap-4">
                                        @if ($authorBio->website)
                                            <a
                                                href="{{ $authorBio->website }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1 text-sm text-lime-400 transition-colors hover:text-lime-300"
                                            >
                                                <svg
                                                    class="size-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
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

                                        @frankenSocialLinks($authorBio)
                                        <a
                                            href="{{ $socialLink['url'] }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 text-sm text-lime-400 transition-colors hover:text-lime-300"
                                            aria-label="{{ $socialLink['label'] }}"
                                        >
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                                                />
                                            </svg>
                                            {{ $socialLink['label'] }}
                                        </a>
                                        @endFrankenSocialLinks
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </article>
    </x-slot>
</x-theme::layouts.main>
