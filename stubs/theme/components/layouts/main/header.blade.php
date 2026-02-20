@php
    use FrankenCms\Settings\GeneralSettings;

    $settings = app(GeneralSettings::class);
    $siteName = $settings->title ?? config('app.name');
@endphp

<header class="border-b border-emerald-500/30 bg-slate-950 shadow-lg shadow-emerald-500/20">
    <nav class="container mx-auto px-4">
        <div class="flex h-16 items-center justify-between">
            {{-- Logo / Site Name with electric gradient --}}
            <div class="flex items-center">
                <a
                    href="/"
                    class="bg-gradient-to-r from-lime-400 via-emerald-400 to-cyan-400 bg-clip-text text-xl font-extrabold text-transparent transition-all duration-300 hover:drop-shadow-[0_0_15px_rgba(163,230,53,0.6)]"
                >
                    {{ $siteName }}
                </a>
            </div>

            {{-- Navigation Menu with electric accents --}}
            <div class="hidden items-center gap-6 md:flex">
                {{-- Example using FrankenCMS @menu directive or global components --}}
                {{-- Option 1: Use global nav component (if available) --}}
                {{-- <x-nav.main /> --}}

                {{-- Option 2: Use @menu directive --}}
                {{--
                    @menu('main-navigation')
                    @foreach ($menuItems as $item)
                    <x-theme::menu-item
                    :item="$item"
                    class="text-sm font-medium text-emerald-200/90 transition-all duration-200 hover:text-lime-400 hover:drop-shadow-[0_0_8px_rgba(163,230,53,0.5)]"
                    />
                    @endforeach
                    @endmenu
                --}}

                {{-- Option 3: Hardcoded links (for template portability) --}}
                <a
                    href="/"
                    class="text-sm font-medium text-emerald-200/90 transition-all duration-200 hover:text-lime-400 hover:drop-shadow-[0_0_8px_rgba(163,230,53,0.5)]"
                >
                    Home
                </a>
                <a
                    href="/about"
                    class="text-sm font-medium text-emerald-200/90 transition-all duration-200 hover:text-lime-400 hover:drop-shadow-[0_0_8px_rgba(163,230,53,0.5)]"
                >
                    About
                </a>
                <a
                    href="/blog"
                    class="text-sm font-medium text-emerald-200/90 transition-all duration-200 hover:text-lime-400 hover:drop-shadow-[0_0_8px_rgba(163,230,53,0.5)]"
                >
                    Blog
                </a>
                <a
                    href="/contact"
                    class="text-sm font-medium text-emerald-200/90 transition-all duration-200 hover:text-lime-400 hover:drop-shadow-[0_0_8px_rgba(163,230,53,0.5)]"
                >
                    Contact
                </a>
            </div>

            {{-- Mobile Menu Button with electric styling --}}
            <div class="md:hidden">
                <button
                    type="button"
                    class="rounded-md p-2 text-lime-400 transition-all duration-200 hover:bg-emerald-500/20 hover:text-cyan-400 hover:shadow-lg hover:shadow-lime-500/30"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    x-data="{ mobileMenuOpen: false }"
                >
                    <span class="sr-only">Open menu</span>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile Menu with laboratory dark theme --}}
        <div class="md:hidden" x-data="{ mobileMenuOpen: false }" x-show="mobileMenuOpen" x-cloak>
            <div
                class="space-y-1 border-t border-emerald-500/30 bg-slate-900 pt-2 pb-3 shadow-inner shadow-emerald-500/10"
            >
                <a
                    href="/"
                    class="block px-3 py-2 text-base font-medium text-emerald-200/90 transition-all duration-200 hover:bg-emerald-500/20 hover:text-lime-400 hover:shadow-inner hover:shadow-lime-500/20"
                >
                    Home
                </a>
                <a
                    href="/about"
                    class="block px-3 py-2 text-base font-medium text-emerald-200/90 transition-all duration-200 hover:bg-emerald-500/20 hover:text-lime-400 hover:shadow-inner hover:shadow-lime-500/20"
                >
                    About
                </a>
                <a
                    href="/blog"
                    class="block px-3 py-2 text-base font-medium text-emerald-200/90 transition-all duration-200 hover:bg-emerald-500/20 hover:text-lime-400 hover:shadow-inner hover:shadow-lime-500/20"
                >
                    Blog
                </a>
                <a
                    href="/contact"
                    class="block px-3 py-2 text-base font-medium text-emerald-200/90 transition-all duration-200 hover:bg-emerald-500/20 hover:text-lime-400 hover:shadow-inner hover:shadow-lime-500/20"
                >
                    Contact
                </a>
            </div>
        </div>
    </nav>
</header>
