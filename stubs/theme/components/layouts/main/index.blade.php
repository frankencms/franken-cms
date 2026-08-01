@props(['main' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @stack('prefetch')
        @head
        @livewireStyles()
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
        @stack('head')
    </head>

    <body
        @class([
            'grid min-h-dvh grid-cols-1 grid-rows-[auto_1fr_auto]',
            'bg-neutral-100 text-neutral-800 antialiased',
            'debug-screens' => config('app.debug'),
        ])
    >
        @stack('body')

        {{-- Skip to Content Link --}}
        <a
            href="#content"
            class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:rounded-md focus:bg-neutral-900 focus:px-4 focus:py-2 focus:text-white focus:shadow-lg"
        >
            Skip to content
        </a>

        <div>
            {{-- Header --}}
            <x-theme::layouts.main.header />

            {{-- Breadcrumbs --}}
            <div class="bg-slate-950">
                <div class="container mx-auto">
                    <x-breadcrumbs class="py-4 text-sm text-emerald-200/80" />
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        @if ($main instanceof \Illuminate\View\ComponentSlot)
            <main id="content" {{ $main->attributes->class([]) }}>
                {{ $main }}
            </main>
        @endif

        {{ $slot }}

        {{-- Footer --}}
        <x-theme::layouts.main.footer />

        @livewireScripts()
        @stack('scripts')

        <x-franken-og-image />
    </body>
</html>
