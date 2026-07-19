{{--
    OG Image Template: Site-wide fallback

    Used when a page has no post-type template, no manually uploaded OG
    image, and no default image in SEO settings. Rendered as the inner
    content of the `<x-og-image>` wrapper, so this file must NOT include
    its own <x-og-image> tag — it is dropped straight into a 1200x630
    canvas and receives $post in scope.

    Enable it in config/franken-cms.php:
    'og_image' => ['default_template' => 'theme.og-templates.default']
--}}
<div class="flex h-full w-full flex-col items-center justify-center bg-slate-950 p-16 text-white">
    <p class="text-3xl tracking-widest text-emerald-200/60 uppercase">{{ setting('general.title') }}</p>

    @if ($post?->post_title)
        <h1
            class="mt-8 bg-gradient-to-r from-lime-400 via-emerald-400 to-cyan-400 bg-clip-text text-center text-6xl leading-tight font-bold text-transparent"
        >
            {{ $post->post_title }}
        </h1>
    @endif

    @if (setting('seo.default_meta_description'))
        <p class="mt-6 max-w-4xl text-center text-2xl text-slate-300">{{ setting('seo.default_meta_description') }}</p>
    @endif
</div>
