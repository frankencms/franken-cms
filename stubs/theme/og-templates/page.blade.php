{{--
    OG Image Template: Page — "Dossier Card"

    The calmer sibling of the post "specimen card": same laboratory DNA
    (grid, voltage line, bolt seam, stitches, gradient title) minus the
    specimen number and article metadata. Rendered on a 1200x630 canvas
    with $post in scope (Page extends Post). Self-contained CSS/fonts.
--}}
@php
    $featured = $post->hasMedia('featured') ? $post->getFirstMedia('featured')->getFullUrl() : null;
    $path = '/' . ltrim($post->post_slug === 'home' ? '' : $post->post_slug, '/');
    $tagline = setting('seo.site_tagline');
    $titleLength = mb_strlen($post->post_title);
    $titleSize = match (true) {
        $titleLength > 90  => '46px',
        $titleLength > 50  => '56px',
        $titleLength > 20  => '70px',
        default            => '96px',
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

    .og-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(110, 231, 183, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(110, 231, 183, 0.05) 1px, transparent 1px);
        background-size: 42px 42px;
        mask-image: radial-gradient(800px 520px at 30% 40%, black 40%, transparent 85%);
    }

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
        width: {{ $featured ? '58%' : '68%' }};
    }

    /* giant schematic bolt watermark for image-less pages */
    .og-bolt-mark {
        position: absolute;
        right: -70px;
        top: 50%;
        transform: translateY(-50%) rotate(14deg);
        font-size: 560px;
        line-height: 1;
        background: linear-gradient(165deg, rgba(163, 230, 53, 0.30) 20%, rgba(52, 211, 153, 0.16) 55%, rgba(34, 211, 238, 0.08) 90%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        filter: drop-shadow(0 0 70px rgba(163, 230, 53, 0.22));
    }

    .og-path {
        display: inline-flex;
        align-items: center;
        gap: 14px;
        align-self: flex-start;
        border: 1.5px solid rgba(163, 230, 53, 0.5);
        padding: 10px 18px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 19px;
        font-weight: 700;
        letter-spacing: 0.16em;
        color: #bef264;
        background: rgba(101, 163, 13, 0.10);
    }

    .og-path .bolt { filter: drop-shadow(0 0 6px rgba(163, 230, 53, 0.8)); }

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

    .og-tagline {
        font-family: 'JetBrains Mono', monospace;
        font-size: 21px;
        font-weight: 500;
        color: rgba(209, 250, 229, 0.75);
        max-height: 1.5em;
        overflow: hidden;
    }

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

    .og-photo::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(115deg, rgba(6, 13, 10, 0.85) 0%, rgba(6, 78, 59, 0.35) 45%, rgba(6, 13, 10, 0.15) 100%);
        mix-blend-mode: multiply;
    }

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
    @else
        <div class="og-bolt-mark">⚡</div>
    @endif

    <div class="og-voltage"></div>

    <div class="og-body">
        <div class="og-path">
            <span class="bolt">⚡</span>
            <span>{{ $path }}</span>
        </div>

        <h1 class="og-title">{{ $post->post_title }}</h1>

        <div>
            @if ($tagline)
                <div class="og-tagline">{{ $tagline }}</div>
            @endif

            <div class="og-footer" style="margin-top: {{ $tagline ? '34px' : '0' }};">
                <span class="dot"></span>
                <span>{{ setting('general.title') }}</span>
            </div>
        </div>
    </div>
</div>
