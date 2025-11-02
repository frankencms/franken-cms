{{-- Home Page Template - Frankenstein Laboratory Theme --}}
<x-theme::layouts.main>
    <x-slot:main>
        {{--
            Hero Section
            Design: Dark gothic laboratory with electric green/cyan accents, lightning energy effects
            Typography: Extra large headlines with electric glow for that mad scientist feel
            Animation: Electric pulse and glow effects on CTAs
        --}}
        {{-- <section --}}
        {{-- class="relative overflow-hidden bg-gradient-to-br from-black via-slate-950 to-emerald-950/20 py-24 text-white md:py-32 lg:py-40" --}}
        {{-- > --}}
        {{--  --}}
        {{-- Electric energy orbs - like lightning in the laboratory --}}
        {{-- <div class="pointer-events-none absolute inset-0 overflow-hidden"> --}}
        {{-- <div --}}
        {{-- class="absolute top-0 -left-1/4 h-96 w-96 rounded-full bg-gradient-to-br from-lime-500/30 to-emerald-500/20 blur-3xl" --}}
        {{-- ></div> --}}
        {{-- <div --}}
        {{-- class="absolute -right-1/4 bottom-0 h-96 w-96 rounded-full bg-gradient-to-br from-cyan-500/30 to-teal-500/20 blur-3xl" --}}
        {{-- ></div> --}}
        {{-- <div --}}
        {{-- class="absolute top-1/2 left-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-br from-lime-400/20 to-transparent blur-3xl" --}}
        {{-- ></div> --}}
        {{-- </div> --}}

        <section
            class="relative overflow-hidden bg-gradient-to-br from-black via-slate-950 to-slate-900 py-20 text-white"
        >
            {{-- Electric Orbs --}}
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div
                    class="absolute top-20 -left-20 h-96 w-96 rounded-full bg-gradient-to-r from-emerald-500/20 to-lime-500/20 blur-3xl"
                ></div>
                <div
                    class="absolute -right-20 bottom-20 h-96 w-96 rounded-full bg-gradient-to-r from-cyan-500/20 to-emerald-500/20 blur-3xl"
                ></div>
            </div>

            <div class="relative z-10 container mx-auto px-4">
                <div class="mx-auto max-w-5xl text-center">
                    @frakenImage(
                        'hero.image',

                        [
                            'class' => 'mx-auto max-h-64 rounded-lg shadow-xl',
                        ]
                    )

                    {{-- Electric headline with toxic green glow effect --}}
                    <h1
                        class="mb-8 bg-gradient-to-r from-lime-400 via-emerald-300 to-cyan-400 bg-clip-text text-5xl leading-tight font-extrabold tracking-tight text-transparent drop-shadow-[0_0_30px_rgba(163,230,53,0.5)] md:text-6xl lg:text-7xl xl:text-8xl"
                    >
                        @frankenText(
                            'hero.title',
                            [
                                'label' => 'Hero Title',
                                'required' => true,
                                'default' => 'Welcome to FrankenCMS',
                                'maxLength' => 100,
                            ]
                        )
                    </h1>

                    {{-- Subtitle with eerie glow --}}
                    <p
                        class="mx-auto mb-12 max-w-3xl text-xl leading-relaxed text-emerald-100/90 md:text-2xl lg:text-3xl"
                    >
                        @frankenTextarea(
                            'hero.subtitle',
                            [
                                'label' => 'Hero Subtitle',
                                'default' => 'A modern content management system built with Laravel and FilamentPHP',
                                'rows' => 2,
                                'maxLength' => 200,
                            ]
                        )
                    </p>

                    {{-- Electric CTA buttons with lightning bolt energy --}}
                    @frankenUrl('hero.primary_cta_url', ['label' => 'Primary Button URL', 'default' => '#features'])
                    @frankenUrl('hero.secondary_cta_url', ['label' => 'Secondary Button URL', 'default' => '#about'])
                    <div class="flex flex-wrap justify-center gap-4 md:gap-6">
                        <a
                            href="{{ $frankenFields['heroPrimaryCtaUrl'] ?? '#features' }}"
                            class="group relative inline-flex items-center justify-center overflow-hidden rounded-xl bg-gradient-to-r from-lime-500 to-emerald-500 px-10 py-4 text-base font-bold text-black shadow-2xl shadow-lime-500/50 transition-all duration-300 hover:scale-105 hover:shadow-[0_0_40px_rgba(163,230,53,0.8)] hover:shadow-lime-400/80 md:text-lg"
                        >
                            <span class="relative z-10">
                                @frankenText('hero.primary_cta_text', ['label' => 'Primary Button Text', 'default' => 'Get Started'])
                            </span>
                            {{-- Electric bolt shine effect --}}
                            <div
                                class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/40 to-transparent transition-transform duration-700 group-hover:translate-x-full"
                            ></div>
                        </a>

                        <a
                            href="{{ $frankenFields['heroSecondaryCtaUrl'] ?? '#about' }}"
                            class="group relative inline-flex items-center justify-center overflow-hidden rounded-xl border-2 border-cyan-400/50 bg-cyan-500/10 px-10 py-4 text-base font-bold text-cyan-300 shadow-xl shadow-cyan-500/30 backdrop-blur-sm transition-all duration-300 hover:scale-105 hover:border-cyan-400 hover:bg-cyan-500/20 hover:text-cyan-200 hover:shadow-2xl hover:shadow-cyan-400/50 md:text-lg"
                        >
                            <span class="relative z-10">
                                @frankenText('hero.secondary_cta_text', ['label' => 'Secondary Button Text', 'default' => 'Learn More'])
                            </span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Electric energy separator line --}}
            <div
                class="absolute right-0 bottom-0 left-0 h-px bg-gradient-to-r from-transparent via-lime-500/70 to-transparent shadow-[0_0_10px_rgba(163,230,53,0.7)]"
            ></div>
        </section>

        {{--
            Features Section
            Design: Dark laboratory surface with toxic chemical glow cards
            Cards: Transform on hover with electric green/cyan glow effects
            Color accents: Laboratory chemical gradients on icons
        --}}
        <section id="features" class="relative bg-gradient-to-b from-slate-950 to-slate-900 py-20 md:py-28 lg:py-32">
            <div class="container mx-auto px-4">
                {{-- Section header with electric glow typography --}}
                <div class="mb-16 text-center md:mb-20">
                    <h2
                        class="mb-6 bg-gradient-to-r from-lime-400 via-emerald-400 to-lime-400 bg-clip-text text-4xl font-extrabold tracking-tight text-transparent drop-shadow-[0_0_20px_rgba(163,230,53,0.3)] md:text-5xl lg:text-6xl"
                    >
                        @frankenText(
                            'features.title',
                            [
                                'label' => 'Features Section Title',
                                'default' => 'Features',
                                'maxLength' => 50,
                            ]
                        )
                    </h2>

                    <p
                        class="mx-auto mb-12 max-w-3xl text-lg leading-relaxed text-emerald-200/80 md:text-xl lg:text-2xl"
                    >
                        @frankenTextarea(
                            'features.subtitle',
                            [
                                'label' => 'Features Section Subtitle',
                                'default' => 'Everything you need to build amazing websites',
                                'rows' => 2,
                            ]
                        )
                    </p>

                    @frankenMediaImage(
                        'features.image',
                        [
                            'class' => 'mx-auto rounded-lg shadow-xl',
                        ]
                    )
                </div>

                {{-- Feature cards grid with electric hover effects --}}
                <div class="grid gap-6 md:grid-cols-2 md:gap-8 lg:grid-cols-3 lg:gap-10">
                    {{-- Use @frankenRepeater block directive --}}
                    @frankenRepeater(
                        'features.items',
                        [
                            'label' => 'Feature Items',
                            'schema' => [
                                // Simple array definition (recommended for most cases)
                                ['name' => 'icon', 'type' => 'text', 'label' => 'Icon (emoji)', 'default' => '🚀'],
                                ['name' => 'title', 'type' => 'text', 'label' => 'Title', 'required' => true],
                                ['name' => 'description', 'type' => 'textarea', 'label' => 'Description', 'rows' => 2],
                                // Or use full Filament classes when you need advanced features:
                                // \Filament\Forms\Components\TextInput::make('icon')->label('Icon (emoji)')->default('🚀'),
                                // \Filament\Forms\Components\TextInput::make('title')->label('Title')->required(),
                                // \Filament\Forms\Components\Textarea::make('description')->label('Description')->rows(2),
                            ],
                            'defaultItems' => 3,
                            'collapsible' => true,
                            'itemLabel' => fn ($state) => $state['title'] ?? 'Feature',
                        ]
                    )
                        <div
                            class="group relative rounded-2xl border border-emerald-500/20 bg-gradient-to-br from-slate-900 to-slate-800 p-8 shadow-lg shadow-emerald-500/10 transition-all duration-300 hover:-translate-y-2 hover:border-lime-400/50 hover:shadow-2xl hover:shadow-lime-500/30"
                        >
                            {{-- Electric energy gradient that appears on hover --}}
                            <div
                                class="absolute inset-0 rounded-2xl bg-gradient-to-br from-lime-500/5 to-cyan-500/5 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                            ></div>

                            <div class="relative z-10">
                                {{-- Icon with toxic chemical glow --}}
                                <div
                                    class="mb-6 inline-flex h-16 w-16 items-center justify-center rounded-xl bg-gradient-to-br from-lime-500/20 to-emerald-500/20 text-4xl shadow-md ring-1 shadow-lime-500/20 ring-lime-400/30 transition-all duration-300 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-lime-400/40 group-hover:ring-lime-400/60"
                                >
                                    {{ $franken->icon ?? '🚀' }}
                                </div>

                                {{-- Feature title with laboratory aesthetic --}}
                                <h3 class="mb-4 text-2xl leading-tight font-bold text-lime-100">
                                    {{ $franken->title }}
                                </h3>

                                {{-- Feature description with eerie text --}}
                                <p class="text-base leading-relaxed text-emerald-200/70">
                                    {{ $franken->description }}
                                </p>
                            </div>

                            {{-- Electric border pulse on hover --}}
                            <div
                                class="absolute inset-x-0 bottom-0 h-1 rounded-b-2xl bg-gradient-to-r from-lime-400 via-cyan-400 to-lime-400 opacity-0 shadow-[0_0_15px_rgba(163,230,53,0.6)] transition-opacity duration-300 group-hover:opacity-100"
                            ></div>
                        </div>
                    @endFrankenRepeater
                </div>
            </div>
        </section>

        {{--
            Call to Action Section
            Design: Electric storm gradient with lightning bolt energy
            Layout: Centered mad scientist call to action
            CTA: Glowing electric button with pulse effects
        --}}
        <section
            class="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-teal-900 to-cyan-900 py-20 text-white md:py-24 lg:py-28"
        >
            {{-- Electric energy pattern background --}}
            <div class="pointer-events-none absolute inset-0 opacity-20">
                <div class="absolute top-0 left-0 h-full w-1/2 bg-gradient-to-br from-lime-500/30 to-transparent"></div>
                <div
                    class="absolute right-0 bottom-0 h-full w-1/2 bg-gradient-to-tl from-cyan-500/30 to-transparent"
                ></div>
            </div>

            {{-- Additional electric orbs for laboratory effect --}}
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute top-1/4 left-1/4 h-48 w-48 rounded-full bg-lime-400/20 blur-3xl"></div>
                <div class="absolute right-1/4 bottom-1/4 h-48 w-48 rounded-full bg-cyan-400/20 blur-3xl"></div>
            </div>

            <div class="relative z-10 container mx-auto px-4">
                <div class="mx-auto max-w-4xl text-center">
                    {{-- CTA headline with electric glow --}}
                    <h2
                        class="mb-6 bg-gradient-to-r from-lime-300 via-emerald-200 to-cyan-300 bg-clip-text text-4xl leading-tight font-extrabold tracking-tight text-transparent drop-shadow-[0_0_25px_rgba(163,230,53,0.4)] md:text-5xl lg:text-6xl"
                    >
                        @frankenText(
                            'cta.title',
                            [
                                'label' => 'CTA Title',
                                'default' => 'Ready to get started?',
                                'maxLength' => 100,
                            ]
                        )
                    </h2>

                    {{-- CTA description with toxic glow --}}
                    <p
                        class="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-emerald-100/90 md:text-xl lg:text-2xl"
                    >
                        @frankenTextarea(
                            'cta.description',
                            [
                                'label' => 'CTA Description',
                                'default' => 'Join thousands of developers building amazing websites with FrankenCMS',
                                'rows' => 2,
                            ]
                        )
                    </p>

                    {{-- High-voltage CTA button with electric pulse --}}
                    @frankenUrl('cta.button_url', ['label' => 'CTA Button URL', 'default' => '/contact'])
                    <a
                        href="{{ $frankenFields['ctaButtonUrl'] ?? '/contact' }}"
                        class="group relative inline-flex items-center justify-center overflow-hidden rounded-xl bg-gradient-to-r from-lime-400 to-emerald-400 px-12 py-5 text-lg font-bold text-black shadow-2xl shadow-lime-500/40 transition-all duration-300 hover:scale-105 hover:shadow-[0_0_50px_rgba(163,230,53,0.9)] hover:shadow-lime-400/70 md:text-xl"
                    >
                        <span class="relative z-10">
                            @frankenText('cta.button_text', ['label' => 'CTA Button Text', 'default' => 'Contact Us'])
                        </span>
                        {{-- Electric bolt shine effect --}}
                        <div
                            class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/50 to-transparent transition-transform duration-700 group-hover:translate-x-full"
                        ></div>
                    </a>
                </div>
            </div>
        </section>
    </x-slot>
</x-theme::layouts.main>
