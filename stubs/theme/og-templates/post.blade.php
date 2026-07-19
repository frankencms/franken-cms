{{--
    OG Image Template: Post — "Specimen Card"

    Rendered as the inner content of the `<x-og-image>` wrapper on a
    1200x630 canvas with $post in scope. Self-contained: fonts + CSS live
    in this file, so no theme rebuild is needed to tweak it.
--}}
@php
    $category = $post->categories()->first()?->name;
    $author = $post->author?->name;
    $readTime = $post->getMeta('read_time');
    $featured = $post->hasMedia('featured') ? $post->getFirstMedia('featured')->getFullUrl() : null;
    $titleLength = mb_strlen($post->post_title);
    $titleSize = match (true) {
        $titleLength > 90 => '44px',
        $titleLength > 50 => '54px',
        default           => '66px',
    };
    $focalCss = $featured ? \FrankenCms\Support\FocalPoint::toCss($post->getFirstMedia('featured')->getCustomProperty('focal_point')) : '';
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=JetBrains+Mono:wght@500;700&display=swap');

    .og-card {
        position: relative;
        width: 1200px;
        height: 630px;
        overflow: hidden;
        background:
            radial-gradient(900px 500px at 12% -10%, rgba(132, 204, 22, 0.14), transparent 60%),
            radial-gradient(700px 420px at 85% 110%, rgba(16, 185, 129, 0.12), transparent 65%),
            #060d0a;
        color: #f0fdf4;
        font-family: 'Bricolage Grotesque', sans-serif;
    }

    /* faint anatomical grid */
    .og-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(110, 231, 183, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(110, 231, 183, 0.05) 1px, transparent 1px);
        background-size: 42px 42px;
        mask-image: radial-gradient(800px 520px at 30% 40%, black 40%, transparent 85%);
    }

    /* electric hairline across the top */
    .og-voltage {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #a3e635, #34d399 45%, #22d3ee 80%, transparent);
        box-shadow: 0 0 24px rgba(163, 230, 53, 0.55);
    }

    .og-body {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 60px 64px 48px;
        width: {{ $featured ? '58%' : '100%' }};
    }

    .og-specimen-label {
        display: inline-flex;
        align-items: center;
        gap: 14px;
        align-self: flex-start;
        border: 1.5px solid rgba(163, 230, 53, 0.5);
        padding: 10px 18px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 19px;
        font-weight: 700;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: #bef264;
        background: rgba(101, 163, 13, 0.10);
    }

    .og-specimen-label .bolt { filter: drop-shadow(0 0 6px rgba(163, 230, 53, 0.8)); }

    .og-title {
        font-size: {{ $titleSize }};
        line-height: 1.06;
        font-weight: 800;
        letter-spacing: -0.015em;
        max-height: 4.4em;
        overflow: hidden;
        background: linear-gradient(100deg, #ecfccb 10%, #a3e635 45%, #34d399 75%, #22d3ee 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        text-wrap: balance;
    }

    .og-meta {
        display: flex;
        align-items: center;
        gap: 18px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 21px;
        font-weight: 500;
        color: rgba(209, 250, 229, 0.75);
    }

    .og-meta .tick { color: #a3e635; }

    .og-footer {
        display: flex;
        align-items: center;
        gap: 14px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 20px;
        font-weight: 700;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: rgba(190, 242, 100, 0.9);
    }

    .og-footer .dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: #a3e635;
        box-shadow: 0 0 14px rgba(163, 230, 53, 0.9);
    }

    /* featured image seamed in with a lightning-bolt edge */
    .og-photo {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 46%;
        clip-path: polygon(24% 0, 100% 0, 100% 100%, 10% 100%, 20% 62%, 8% 58%, 26% 24%, 16% 22%);
    }

    .og-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        {{ $focalCss }}
    }

    /* emerald duotone wash over the photo */
    .og-photo::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(115deg, rgba(6, 13, 10, 0.85) 0%, rgba(6, 78, 59, 0.35) 45%, rgba(6, 13, 10, 0.15) 100%);
        mix-blend-mode: multiply;
    }

    /* suture stitches along the seam */
    .og-stitches {
        position: absolute;
        top: 0;
        bottom: 0;
        right: calc(46% - 24px);
        width: 4px;
        background: repeating-linear-gradient(
            to bottom,
            transparent 0 26px,
            rgba(163, 230, 53, 0.55) 26px 54px
        );
        transform: rotate(3.5deg);
        transform-origin: top;
    }

    .og-stitches::before {
        content: '';
        position: absolute;
        inset: 0 -10px;
        background: repeating-linear-gradient(
            to bottom,
            transparent 0 36px,
            rgba(163, 230, 53, 0.45) 36px 39px,
            transparent 39px 80px
        );
    }
</style>

<div class="og-card">
    <div class="og-grid"></div>

    @if ($featured)
        <div class="og-photo">
            <img src="{{ $featured }}" alt="" />
        </div>
        <div class="og-stitches"></div>
    @endif

    <div class="og-voltage"></div>

    <div class="og-body">
        <div class="og-specimen-label">
            <span class="bolt">⚡</span>
            <span>{{ $category ?? 'Specimen' }} · No. {{ str_pad($post->id, 3, '0', STR_PAD_LEFT) }}</span>
        </div>

        <h1 class="og-title">{{ $post->post_title }}</h1>

        <div>
            <div class="og-meta">
                @if ($author)
                    <span>{{ $author }}</span>
                    <span class="tick">⌁</span>
                @endif
                @if ($post->post_published_at)
                    <span>{{ $post->post_published_at->format('M j, Y') }}</span>
                @endif
                @if ($readTime)
                    <span class="tick">⌁</span>
                    <span>{{ $readTime }} min read</span>
                @endif
            </div>

            <div class="og-footer" style="margin-top: 34px;">
                <span class="dot"></span>
                <span>{{ setting('general.title') }}</span>
            </div>
        </div>
    </div>
</div>
