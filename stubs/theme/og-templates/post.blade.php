{{--
    OG Image Template: Post

    Rendered as the inner content of the `<x-og-image>` wrapper (see
    resources/views/components/og-image.blade.php), so this file must NOT
    include its own <x-og-image> tag — it is dropped straight into a
    1200x630 canvas and receives $post in scope.
--}}
<div class="flex h-full w-full flex-col justify-between bg-slate-950 p-16 text-white">
    <div class="flex items-center gap-4">
        @if ($post->hasMedia('featured'))
            <img
                src="{{ $post->getFirstMedia('featured')->getFullUrl() }}"
                class="h-24 w-24 rounded-xl object-cover ring-1 ring-emerald-500/30"
                alt=""
            />
        @endif
        <p class="text-2xl tracking-widest text-emerald-200/60 uppercase">{{ setting('general.title') }}</p>
    </div>

    <h1
        class="bg-gradient-to-r from-lime-400 via-emerald-400 to-cyan-400 bg-clip-text text-7xl leading-tight font-bold text-transparent"
    >
        {{ $post->post_title }}
    </h1>

    @if ($post->post_published_at)
        <p class="text-2xl text-emerald-200/70">{{ $post->post_published_at->format('F j, Y') }}</p>
    @endif
</div>
