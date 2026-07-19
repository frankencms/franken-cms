{{-- Taxonomy Archive Template (Category, Tag, etc.) --}}
<x-theme::layouts.main>
    <x-slot:main>
        <div class="bg-slate-950 py-12">
            <div class="container mx-auto px-4">
                {{-- Archive Header --}}
                <header class="mb-12 text-center">
                    <div class="mb-2 text-sm font-medium tracking-wide text-emerald-200/70 uppercase">
                        {{ ucfirst($taxonomy->name) }} Archive
                    </div>

                    <h1
                        class="mb-4 bg-gradient-to-r from-lime-400 via-emerald-400 to-cyan-400 bg-clip-text text-4xl font-bold text-transparent drop-shadow-[0_0_20px_rgba(163,230,53,0.3)] md:text-5xl"
                    >
                        {{ $term->name }}
                    </h1>

                    @if ($term->description)
                        <div class="mx-auto max-w-2xl text-lg text-emerald-200/80">
                            {{ $term->description }}
                        </div>
                    @endif
                </header>

                {{-- Posts Grid --}}
                @if ($posts->count() > 0)
                    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($posts as $post)
                            <article
                                class="group flex flex-col overflow-hidden rounded-lg border border-emerald-500/20 bg-gradient-to-br from-slate-900 to-slate-800 shadow-[0_0_20px_rgba(16,185,129,0.1)] transition-all duration-300 hover:border-lime-400/40 hover:shadow-[0_0_30px_rgba(163,230,53,0.2)]"
                            >
                                {{-- Featured Image --}}
                                @if ($post->hasMedia('featured'))
                                    @php
                                        $media = $post->getFirstMedia('featured');
                                        $cssClasses = $media->getCustomProperty('css_classes', '');
                                        $lazyLoading = $media->getCustomProperty('lazy_loading', true);
                                        $title = $media->getCustomProperty('title');
                                        $alt = $media->getCustomProperty('alt', $post->post_title);
                                        $objectPosition = str_replace(['object-position: ', ';'], '', \FrankenCms\Support\FocalPoint::toCss($media->getCustomProperty('focal_point')));

                                        $attributes = [
                                            'class' => trim('h-48 w-full object-cover transition-transform duration-300 group-hover:scale-105 ' . $cssClasses),
                                            'sizes' => '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw',
                                            'loading' => $lazyLoading ? 'lazy' : 'eager',
                                            'alt' => $alt,
                                            'title' => $title,
                                            'style' => "object-position: $objectPosition;",
                                        ];
                                    @endphp

                                    <a href="{{ $post->url }}" class="block overflow-hidden">
                                        {!! $media('listing', $attributes) !!}
                                    </a>
                                @else
                                    @php
                                        // Try to use default featured image from settings
                                        $defaultImage = \FrankenCms\Models\SiteSettingsMedia::getInstance()->getFirstMedia('default-featured');
                                    @endphp

                                    @if ($defaultImage)
                                        @php
                                            $cssClasses = $defaultImage->getCustomProperty('css_classes', '');
                                            $lazyLoading = $defaultImage->getCustomProperty('lazy_loading', true);
                                            $title = $defaultImage->getCustomProperty('title');
                                            $alt = $defaultImage->getCustomProperty('alt', 'Default featured image');
                                            $objectPosition = str_replace(['object-position: ', ';'], '', \FrankenCms\Support\FocalPoint::toCss($defaultImage->getCustomProperty('focal_point')));

                                            $attributes = [
                                                'class' => trim('h-48 w-full object-cover transition-transform duration-300 group-hover:scale-105 ' . $cssClasses),
                                                'sizes' => '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw',
                                                'loading' => $lazyLoading ? 'lazy' : 'eager',
                                                'alt' => $alt,
                                                'title' => $title,
                                                'style' => "object-position: $objectPosition;",
                                            ];
                                        @endphp

                                        <a href="{{ $post->url }}" class="block overflow-hidden">
                                            {!! $defaultImage('listing', $attributes) !!}
                                        </a>
                                    @else
                                        <div class="h-48 w-full bg-gradient-to-br from-slate-800 to-slate-900"></div>
                                    @endif
                                @endif

                                <div class="flex flex-1 flex-col p-6">
                                    {{-- Categories (show if viewing tag archive, hide if viewing category archive) --}}
                                    @if ($taxonomy->name !== 'category' && $post->categories()->exists())
                                        <div class="mb-3 flex flex-wrap gap-2">
                                            @foreach ($post->categories->take(2) as $category)
                                                <a
                                                    href="{{ $category->url }}"
                                                    class="rounded-full bg-gradient-to-r from-lime-500/20 to-emerald-500/20 px-3 py-1 text-xs font-medium text-lime-300 ring-1 ring-lime-400/30 transition-all hover:from-lime-500/30 hover:to-emerald-500/30 hover:ring-lime-400/50"
                                                >
                                                    {{ $category->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Post Title --}}
                                    <h2 class="mb-3 text-xl font-bold text-lime-100">
                                        <a href="{{ $post->url }}" class="transition-colors hover:text-lime-400">
                                            {{ $post->post_title }}
                                        </a>
                                    </h2>

                                    {{-- Post Teaser/Excerpt --}}
                                    @if ($post->getMeta('post_teaser'))
                                        <p class="mb-4 line-clamp-3 flex-1 text-sm text-emerald-200/70">
                                            {{ $post->getMeta('post_teaser') }}
                                        </p>
                                    @endif

                                    {{-- Post Meta --}}
                                    <div
                                        class="mt-auto flex items-center justify-between border-t border-emerald-500/20 pt-4 text-xs text-emerald-200/60"
                                    >
                                        <div class="flex items-center gap-3">
                                            @if ($post->author)
                                                <span>{{ $post->author->name }}</span>
                                            @endif

                                            @if ($post->post_published_at)
                                                <time datetime="{{ $post->post_published_at->toIso8601String() }}">
                                                    {{ $post->post_published_at->format('M j, Y') }}
                                                </time>
                                            @endif
                                        </div>

                                        @if ($post->getMeta('read_time'))
                                            <span>{{ $post->getMeta('read_time') }} min</span>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-12">
                        {{ $posts->links() }}
                    </div>
                @else
                    <div class="py-12 text-center text-emerald-200/70">
                        <p>No posts found in this {{ $taxonomy->name }}.</p>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>
</x-theme::layouts.main>
