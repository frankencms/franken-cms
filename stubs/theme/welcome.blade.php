{{-- Welcome/Setup Template - Shown when no homepage is configured --}}
<x-theme::layouts.main>
    <x-slot:main>
        {{-- Hero Section --}}
        <section class="relative overflow-hidden bg-gradient-to-br from-black via-emerald-950 to-slate-900 py-20 text-white">
            {{-- Electric Orbs Background --}}
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute left-1/4 top-20 h-96 w-96 rounded-full bg-lime-500/10 blur-3xl"></div>
                <div class="absolute right-1/4 top-40 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl"></div>
                <div class="absolute bottom-20 left-1/3 h-72 w-72 rounded-full bg-emerald-500/10 blur-3xl"></div>
            </div>

            <div class="container relative mx-auto px-4">
                <div class="mx-auto max-w-4xl text-center">
                    <div class="mb-6 text-6xl drop-shadow-[0_0_20px_rgba(163,230,53,0.5)]">⚡</div>
                    <h1 class="mb-6 bg-gradient-to-r from-lime-400 via-emerald-400 to-cyan-400 bg-clip-text text-5xl font-bold text-transparent drop-shadow-[0_0_30px_rgba(163,230,53,0.3)] md:text-6xl lg:text-7xl">
                        Welcome to FrankenCMS
                    </h1>
                    <p class="mb-8 text-xl text-emerald-200/90 md:text-2xl">
                        Your site is almost ready! Just a few quick settings to configure.
                    </p>
                </div>
            </div>
        </section>

        {{-- Setup Instructions --}}
        <section class="bg-slate-950 py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-4xl">
                    <div class="mb-12 text-center">
                        <h2 class="mb-4 bg-gradient-to-r from-lime-400 via-emerald-400 to-cyan-400 bg-clip-text text-4xl font-bold text-transparent drop-shadow-[0_0_20px_rgba(163,230,53,0.3)]">
                            Let's Get Your Site Set Up
                        </h2>
                        <p class="text-lg text-emerald-200/80">
                            Follow these steps to configure your FrankenCMS site
                        </p>
                    </div>

                    {{-- Setup Steps --}}
                    <div class="space-y-6">
                        {{-- Step 1 --}}
                        <div class="rounded-lg border-l-4 border-lime-400 bg-gradient-to-br from-slate-900 to-slate-800 p-6 shadow-[0_0_20px_rgba(163,230,53,0.1)]">
                            <div class="mb-2 flex items-center">
                                <span
                                    class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-lime-500 to-emerald-500 text-sm font-bold text-slate-950 shadow-[0_0_15px_rgba(163,230,53,0.4)]"
                                >
                                    1
                                </span>
                                <h3 class="bg-gradient-to-r from-lime-400 to-emerald-400 bg-clip-text text-xl font-bold text-transparent">
                                    Create Your Homepage
                                </h3>
                            </div>
                            <p class="ml-11 text-emerald-200/70">
                                Go to <strong class="text-lime-300">Admin → Pages → Create Page</strong> and create a page to serve as your homepage.
                                You can choose from templates like "Home Page" or "About Page".
                            </p>
                        </div>

                        {{-- Step 2 --}}
                        <div class="rounded-lg border-l-4 border-emerald-400 bg-gradient-to-br from-slate-900 to-slate-800 p-6 shadow-[0_0_20px_rgba(16,185,129,0.1)]">
                            <div class="mb-2 flex items-center">
                                <span
                                    class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500 text-sm font-bold text-slate-950 shadow-[0_0_15px_rgba(16,185,129,0.4)]"
                                >
                                    2
                                </span>
                                <h3 class="bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-xl font-bold text-transparent">
                                    Create Your Blog Posts Page
                                </h3>
                            </div>
                            <p class="ml-11 text-emerald-200/70">
                                Create another page with the "Blog Posts" template. This page will display your blog posts listing.
                            </p>
                        </div>

                        {{-- Step 3 --}}
                        <div class="rounded-lg border-l-4 border-cyan-400 bg-gradient-to-br from-slate-900 to-slate-800 p-6 shadow-[0_0_20px_rgba(34,211,238,0.1)]">
                            <div class="mb-2 flex items-center">
                                <span
                                    class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-cyan-500 to-lime-500 text-sm font-bold text-slate-950 shadow-[0_0_15px_rgba(34,211,238,0.4)]"
                                >
                                    3
                                </span>
                                <h3 class="bg-gradient-to-r from-cyan-400 to-lime-400 bg-clip-text text-xl font-bold text-transparent">
                                    Configure Reading Settings
                                </h3>
                            </div>
                            <p class="ml-11 mb-3 text-emerald-200/70">
                                Go to <strong class="text-lime-300">Admin → CMS Settings → Reading</strong> and configure:
                            </p>
                            <ul class="ml-11 list-inside list-disc space-y-1 text-emerald-200/70">
                                <li><strong class="text-lime-300">Homepage:</strong> Select the page you created in step 1</li>
                                <li><strong class="text-lime-300">Posts Page:</strong> Select the blog page you created in step 2</li>
                                <li><strong class="text-lime-300">Posts Per Page:</strong> Set how many posts to show per page (default: 10)</li>
                            </ul>
                        </div>

                        {{-- Step 4 --}}
                        <div class="rounded-lg border-l-4 border-lime-500 bg-gradient-to-br from-slate-900 to-slate-800 p-6 shadow-[0_0_20px_rgba(132,204,22,0.1)]">
                            <div class="mb-2 flex items-center">
                                <span
                                    class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-lime-500 to-cyan-500 text-sm font-bold text-slate-950 shadow-[0_0_15px_rgba(132,204,22,0.4)]"
                                >
                                    4
                                </span>
                                <h3 class="bg-gradient-to-r from-lime-400 to-cyan-400 bg-clip-text text-xl font-bold text-transparent">
                                    Optional: Configure Permalinks
                                </h3>
                            </div>
                            <p class="ml-11 text-emerald-200/70">
                                Go to <strong class="text-lime-300">Admin → CMS Settings → Permalinks</strong> to customize your URL structure for posts.
                                Choose from formats like <code
                                    class="rounded bg-lime-500/20 px-2 py-1 font-mono text-sm text-lime-300 ring-1 ring-lime-400/30"
                                >/post-name/</code> or
                                <code class="rounded bg-lime-500/20 px-2 py-1 font-mono text-sm text-lime-300 ring-1 ring-lime-400/30">/2024/12/post-name/</code>.
                            </p>
                        </div>
                    </div>

                    {{-- Quick Access Buttons --}}
                    <div class="mt-12 text-center">
                        <h3 class="mb-6 bg-gradient-to-r from-lime-400 to-emerald-400 bg-clip-text text-2xl font-bold text-transparent">
                            Quick Links
                        </h3>
                        <div class="flex flex-wrap justify-center gap-4">
                            <a
                                href="/admin/page/pages/create"
                                class="rounded-lg bg-gradient-to-r from-lime-500 to-emerald-500 px-6 py-3 font-semibold text-slate-950 shadow-[0_0_20px_rgba(163,230,53,0.3)] transition-all hover:scale-105 hover:shadow-[0_0_30px_rgba(163,230,53,0.5)]"
                            >
                                Create a Page
                            </a>
                            <a
                                href="/admin/cms-settings"
                                class="rounded-lg bg-gradient-to-r from-emerald-500 to-cyan-500 px-6 py-3 font-semibold text-slate-950 shadow-[0_0_20px_rgba(16,185,129,0.3)] transition-all hover:scale-105 hover:shadow-[0_0_30px_rgba(16,185,129,0.5)]"
                            >
                                CMS Settings
                            </a>
                            <a
                                href="/admin"
                                class="rounded-lg border-2 border-lime-400 px-6 py-3 font-semibold text-lime-300 transition-all hover:bg-lime-400 hover:text-slate-950 hover:shadow-[0_0_20px_rgba(163,230,53,0.4)]"
                            >
                                Admin Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Theme Info Section --}}
        <section class="bg-slate-900 py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="mb-4 bg-gradient-to-r from-lime-400 to-emerald-400 bg-clip-text text-3xl font-bold text-transparent drop-shadow-[0_0_20px_rgba(163,230,53,0.3)]">
                        About This Theme
                    </h2>
                    <p class="mb-6 text-lg text-emerald-200/80">
                        You're using the <strong class="text-lime-300">FrankenCMS Default Theme</strong> - a modern, responsive starter theme built with
                        Tailwind CSS.
                    </p>
                    <p class="text-emerald-200/70">
                        This theme includes templates for pages, blog posts, category archives, and more. Customize it to match
                        your brand or use it as a foundation for your own custom theme.
                    </p>
                </div>
            </div>
        </section>

        {{-- Help Section --}}
        <section class="bg-black py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="mb-4 bg-gradient-to-r from-cyan-400 to-lime-400 bg-clip-text text-3xl font-bold text-transparent drop-shadow-[0_0_20px_rgba(34,211,238,0.3)]">
                        Need Help?
                    </h2>
                    <p class="mb-8 text-lg text-emerald-200/80">
                        Check out the documentation or reach out to the community for support.
                    </p>
                    <div class="flex flex-wrap justify-center gap-4">
                        <a
                            href="https://github.com/frankencms/franken-cms"
                            target="_blank"
                            class="rounded-lg border-2 border-emerald-500/30 px-6 py-3 font-semibold text-lime-300 transition-all hover:border-lime-400 hover:bg-lime-400/10 hover:shadow-[0_0_20px_rgba(163,230,53,0.2)]"
                        >
                            📚 Documentation
                        </a>
                        <a
                            href="https://github.com/frankencms/franken-cms/discussions"
                            target="_blank"
                            class="rounded-lg border-2 border-emerald-500/30 px-6 py-3 font-semibold text-lime-300 transition-all hover:border-lime-400 hover:bg-lime-400/10 hover:shadow-[0_0_20px_rgba(163,230,53,0.2)]"
                        >
                            💬 Community
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </x-slot>
</x-theme::layouts.main>
