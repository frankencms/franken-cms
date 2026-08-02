@php
    use FrankenCms\Settings\GeneralSettings;

    $settings = app(GeneralSettings::class);
    $siteName = $settings->title ?? config('app.name');
@endphp

<footer class="border-t border-lime-500/30 bg-gradient-to-b from-slate-900 to-slate-950 py-12 shadow-[0_-10px_40px_rgba(163,230,53,0.1)]">
    <div class="container mx-auto px-4">
        {{-- Main Footer Content --}}
        <div class="mb-8 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
            {{-- Company Info with electric branding --}}
            <div class="space-y-4">
                <h3 class="bg-gradient-to-r from-lime-400 to-emerald-400 bg-clip-text text-lg font-extrabold text-transparent drop-shadow-[0_0_12px_rgba(163,230,53,0.3)]">
                    {{ $siteName }}
                </h3>
                <p class="max-w-xs text-sm leading-relaxed text-emerald-200/70">
                    A modern content management system built with Laravel and FilamentPHP.
                </p>
            </div>

            {{-- Quick Links with electric hover effects --}}
            <div class="space-y-4">
                <h3 class="bg-gradient-to-r from-lime-400 to-emerald-400 bg-clip-text text-lg font-extrabold text-transparent drop-shadow-[0_0_12px_rgba(163,230,53,0.3)]">
                    Quick Links
                </h3>
                <ul class="space-y-2 text-sm">
                    {{-- Can use @menu directive or global components here too --}}
                    <li>
                        <a
                            href="/"
                            class="text-emerald-200/80 transition-all duration-200 hover:text-lime-400 hover:drop-shadow-[0_0_8px_rgba(163,230,53,0.5)]"
                        >
                            Home
                        </a>
                    </li>
                    <li>
                        <a
                            href="/about"
                            class="text-emerald-200/80 transition-all duration-200 hover:text-lime-400 hover:drop-shadow-[0_0_8px_rgba(163,230,53,0.5)]"
                        >
                            About
                        </a>
                    </li>
                    <li>
                        <a
                            href="/blog"
                            class="text-emerald-200/80 transition-all duration-200 hover:text-lime-400 hover:drop-shadow-[0_0_8px_rgba(163,230,53,0.5)]"
                        >
                            Blog
                        </a>
                    </li>
                    <li>
                        <a
                            href="/contact"
                            class="text-emerald-200/80 transition-all duration-200 hover:text-lime-400 hover:drop-shadow-[0_0_8px_rgba(163,230,53,0.5)]"
                        >
                            Contact
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Resources with laboratory aesthetic --}}
            <div class="space-y-4">
                <h3 class="bg-gradient-to-r from-lime-400 to-emerald-400 bg-clip-text text-lg font-extrabold text-transparent drop-shadow-[0_0_12px_rgba(163,230,53,0.3)]">
                    Resources
                </h3>
                <ul class="space-y-2 text-sm">
                    <li>
                        <a
                            href="/privacy"
                            class="text-emerald-200/80 transition-all duration-200 hover:text-lime-400 hover:drop-shadow-[0_0_8px_rgba(163,230,53,0.5)]"
                        >
                            Privacy Policy
                        </a>
                    </li>
                    <li>
                        <a
                            href="/terms"
                            class="text-emerald-200/80 transition-all duration-200 hover:text-lime-400 hover:drop-shadow-[0_0_8px_rgba(163,230,53,0.5)]"
                        >
                            Terms of Service
                        </a>
                    </li>
                    <li>
                        <a
                            href="/cookies"
                            class="text-emerald-200/80 transition-all duration-200 hover:text-lime-400 hover:drop-shadow-[0_0_8px_rgba(163,230,53,0.5)]"
                        >
                            Cookie Policy
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Social Media with electric glow icons --}}
            <div class="space-y-4">
                <h3 class="bg-gradient-to-r from-lime-400 to-emerald-400 bg-clip-text text-lg font-extrabold text-transparent drop-shadow-[0_0_12px_rgba(163,230,53,0.3)]">
                    Connect
                </h3>
                <div class="flex gap-4">
                    <a
                        href="#"
                        class="text-emerald-200/80 transition-all duration-300 hover:scale-110 hover:text-cyan-400 hover:drop-shadow-[0_0_12px_rgba(34,211,238,0.6)]"
                    >
                        <span class="sr-only">Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                        </svg>
                    </a>
                    <a
                        href="#"
                        class="text-emerald-200/80 transition-all duration-300 hover:scale-110 hover:text-cyan-400 hover:drop-shadow-[0_0_12px_rgba(34,211,238,0.6)]"
                    >
                        <span class="sr-only">GitHub</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                fill-rule="evenodd"
                                d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Copyright with electric separator --}}
        <div class="border-t border-emerald-500/30 pt-8 text-center">
            <p class="text-sm text-emerald-200/60">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
        </div>
    </div>
</footer>
