{{--
    OG Image Template: Site-wide fallback — "Transmission Card"

    The most generic member of the specimen/dossier family: fires for any
    post type with no mapped template (when the page also has no uploaded
    OG image and no default image in SEO settings). Rendered on a 1200x630
    canvas with $post in scope. Self-contained CSS/fonts.

    Enable it in config/franken-cms.php:
    'og_image' => ['default_template' => 'theme.og-templates.default']
--}}
@php
    $title = $post->post_title ?: setting('general.title');
    $tagline = setting('seo.site_tagline') ?: setting('seo.default_meta_description');
    $type = strtoupper($post->post_type ?? 'post');
    $titleLength = mb_strlen($title);
    $titleSize = match (true) {
        $titleLength > 90 => '46px',
        $titleLength > 50 => '56px',
        $titleLength > 20 => '70px',
        default           => '96px',
    };
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
            radial-gradient(700px 420px at 85% 110%, rgba(16, 185, 129, 0.12), transparent 65%), #060d0a;
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
        width: 68%;
    }

    .og-chip {
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
        background: rgba(101, 163, 13, 0.1);
    }

    .og-chip .bolt {
        filter: drop-shadow(0 0 6px rgba(163, 230, 53, 0.8));
    }

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
        max-height: 3em;
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

    .og-bolt-mark {
        position: absolute;
        right: -70px;
        top: 50%;
        transform: translateY(-50%) rotate(14deg);
        font-size: 560px;
        line-height: 1;
        background: linear-gradient(
            165deg,
            rgba(163, 230, 53, 0.3) 20%,
            rgba(52, 211, 153, 0.16) 55%,
            rgba(34, 211, 238, 0.08) 90%
        );
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        filter: drop-shadow(0 0 70px rgba(163, 230, 53, 0.22));
    }
</style>

<div class="og-card">
    <div class="og-grid"></div>
    <div class="og-bolt-mark">⚡</div>
    <div class="og-voltage"></div>

    <div class="og-body">
        <div class="og-chip">
            <span class="bolt">⚡</span>
            <span>{{ $type }}</span>
        </div>

        <h1 class="og-title">{{ $title }}</h1>

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
