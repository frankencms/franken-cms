{{-- Post Index / Blog Listing Page Template --}}
{{-- $posts and $page are passed from RouteController --}}

<x-theme::layouts.main>
    <x-slot:main>
        <div class="bg-slate-950 py-12">
            <div class="container mx-auto px-4">
                {{-- Page Header --}}
                <header class="mb-12 text-center">
                    <h1
                        class="mb-4 bg-gradient-to-r from-lime-400 via-emerald-400 to-cyan-400 bg-clip-text text-4xl font-bold text-transparent drop-shadow-[0_0_20px_rgba(163,230,53,0.5)] md:text-5xl"
                    >
                        {{ $page->post_title }}
                    </h1>

                    @if ($page->post_content)
                        <div
                            class="mx-auto prose prose-lg prose-invert prose-headings:bg-gradient-to-r prose-headings:from-lime-400 prose-headings:to-emerald-400 prose-headings:bg-clip-text prose-headings:text-transparent prose-p:text-emerald-200/80 prose-a:text-cyan-400 hover:prose-a:text-cyan-300"
                        >
                            {!! $page->renderRichContent('post_content') !!}
                        </div>
                    @endif
                </header>

                {{-- Posts Grid --}}
                @if ($posts->count() > 0)
                    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($posts as $post)
                            <article
                                class="group flex flex-col overflow-hidden rounded-lg border border-emerald-500/30 bg-gradient-to-br from-slate-900 to-slate-800 shadow-[0_0_20px_rgba(163,230,53,0.2)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_0_30px_rgba(163,230,53,0.4)]"
                            >
                                {{-- Featured Image --}}

                                @if ($post->hasMedia('featured'))
                                    @php
                                        $media = $post->getFirstMedia('featured');
                                        $cssClasses = $media->getCustomProperty('css_classes', '');
                                        $lazyLoading = $media->getCustomProperty('lazy_loading', true);
                                        $title = $media->getCustomProperty('title');
                                        $alt = $media->getCustomProperty('alt');
                                        $focalPoint = $media->getCustomProperty('focal_point', ['x' => 50, 'y' => 50]);

                                        // Build focal point style
                                        $focalX = $focalPoint['x'] ?? 50;
                                        $focalY = $focalPoint['y'] ?? 50;
                                        $objectPosition = "object-position: $focalX% $focalY%;";

                                        $attributes = [
                                            'class' => trim('h-48 w-full object-cover transition-transform duration-300 group-hover:scale-105 ' . $cssClasses),
                                            'sizes' => '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw',
                                            'loading' => $lazyLoading ? 'lazy' : 'eager',
                                            'alt' => $alt,
                                            'title' => $title,
                                            'style' => $objectPosition,
                                        ];
                                    @endphp

                                    <a href="{{ $post->url }}" class="relative block overflow-hidden">
                                        <div
                                            class="absolute inset-0 z-10 bg-gradient-to-t from-slate-900/50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                        ></div>

                                        {!! $media('listing', $attributes) !!}

                                        <div
                                            class="absolute inset-0 ring-1 ring-emerald-500/20 transition-all duration-300 ring-inset group-hover:ring-emerald-400/40"
                                        ></div>
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
                                            $focalPoint = $defaultImage->getCustomProperty('focal_point', ['x' => 50, 'y' => 50]);

                                            // Build focal point style
                                            $focalX = $focalPoint['x'] ?? 50;
                                            $focalY = $focalPoint['y'] ?? 50;
                                            $objectPosition = "object-position: $focalX% $focalY%;";

                                            $attributes = [
                                                'class' => trim('h-48 w-full object-cover transition-transform duration-300 group-hover:scale-105 ' . $cssClasses),
                                                'sizes' => '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw',
                                                'loading' => $lazyLoading ? 'lazy' : 'eager',
                                                'alt' => $alt,
                                                'title' => $title,
                                                'style' => $objectPosition,
                                            ];
                                        @endphp

                                        <a href="{{ $post->url }}" class="relative block overflow-hidden">
                                            <div
                                                class="absolute inset-0 z-10 bg-gradient-to-t from-slate-900/50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                            ></div>

                                            {!! $defaultImage('listing', $attributes) !!}

                                            <div
                                                class="absolute inset-0 ring-1 ring-emerald-500/20 transition-all duration-300 ring-inset group-hover:ring-emerald-400/40"
                                            ></div>
                                        </a>
                                    @else
                                        <div
                                            class="h-48 w-full bg-gradient-to-br from-emerald-900/20 to-cyan-900/20 ring-1 ring-emerald-500/20 ring-inset"
                                        ></div>
                                    @endif
                                @endif

                                <div class="flex flex-1 flex-col p-6">
                                    {{-- Categories --}}
                                    @if ($post->categories()->exists())
                                        <div class="mb-3 flex flex-wrap gap-2">
                                            @foreach ($post->categories->take(2) as $category)
                                                <a
                                                    href="{{ $category->url }}"
                                                    class="rounded-full bg-gradient-to-r from-lime-500/10 to-emerald-500/10 px-3 py-1 text-xs font-medium text-lime-400 ring-1 ring-lime-400/30 transition-all duration-300 hover:from-lime-500/20 hover:to-emerald-500/20 hover:text-lime-300 hover:shadow-[0_0_10px_rgba(163,230,53,0.3)] hover:ring-lime-400/50"
                                                >
                                                    {{ $category->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Post Title --}}
                                    <h2 class="mb-3 text-xl font-bold text-lime-100">
                                        <a
                                            href="{{ $post->url }}"
                                            class="transition-all duration-300 hover:bg-gradient-to-r hover:from-lime-400 hover:to-emerald-400 hover:bg-clip-text hover:text-transparent hover:drop-shadow-[0_0_10px_rgba(163,230,53,0.4)]"
                                        >
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
                                                <span class="text-lime-400/80">{{ $post->author->name }}</span>
                                            @endif

                                            @if ($post->post_published_at)
                                                <time datetime="{{ $post->post_published_at->toIso8601String() }}">
                                                    {{ $post->post_published_at->format('M j, Y') }}
                                                </time>
                                            @endif
                                        </div>

                                        @if ($post->getMeta('read_time'))
                                            <span class="text-cyan-400/80">{{ $post->getMeta('read_time') }} min</span>
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
                    <div
                        class="rounded-lg border border-emerald-500/30 bg-gradient-to-br from-slate-900 to-slate-800 py-12 text-center shadow-[0_0_20px_rgba(163,230,53,0.2)]"
                    >
                        <p class="text-emerald-200/70">No posts found.</p>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>
</x-theme::layouts.main>
