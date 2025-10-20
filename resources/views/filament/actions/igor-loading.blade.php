{{-- Loading overlay that appears when form is submitting --}}
<div
    x-data="{ loading: false }"
    x-init="
        $el.closest('form').addEventListener('submit', () => {
            loading = true
        })
    "
>
    {{-- Loading Overlay --}}
    <div
        x-show="loading"
        x-cloak
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 backdrop-blur-sm dark:bg-gray-950/80"
        style="display: none"
    >
        <div
            class="mx-4 w-full max-w-lg"
            x-transition:enter="transition delay-100 duration-300 ease-out"
            x-transition:enter-start="scale-95 opacity-0"
            x-transition:enter-end="scale-100 opacity-100"
        >
            {{-- Main Card --}}
            <div class="rounded-2xl bg-white p-8 shadow-2xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                {{-- Animated Icon Container --}}
                <div class="mb-6 flex justify-center">
                    <div class="relative inline-flex items-center justify-center">
                        {{-- Pulsing background glow --}}
                        <div
                            class="absolute inset-0 animate-pulse rounded-full bg-primary-500/20 blur-xl dark:bg-primary-400/20"
                        ></div>

                        {{-- Icon circle --}}
                        <div
                            class="relative flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-primary-50 to-primary-100 ring-4 ring-primary-100 dark:from-primary-950/50 dark:to-primary-900/50 dark:ring-primary-900/50"
                        >
                            <x-frankencms-igor class="size-16 animate-pulse text-primary-600 dark:text-primary-400" />
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="space-y-4 text-center">
                    {{-- Title --}}
                    <div>
                        <h3 class="mb-1 text-xl font-bold text-gray-950 dark:text-white">Igor is in the lab...</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Brewing up something brilliant</p>
                    </div>

                    {{-- Rotating Messages --}}
                    <div
                        class="rounded-lg border border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/50"
                    >
                        <p
                            class="min-h-[1.5rem] text-base font-medium text-gray-700 dark:text-gray-300"
                            x-data="{
                                messages: [
                                    'Gathering lightning bolts... ⚡',
                                    'Mixing creative potions... 🧪',
                                    'Consulting ancient texts... 📜',
                                    'Sparking brilliant ideas... 💡',
                                    'Crafting your masterpiece... 🎨',
                                    'Adding a touch of genius... ✨',
                                ],
                                currentMessage: 'Gathering lightning bolts... ⚡',
                                interval: null,
                            }"
                            x-init="
                                interval = setInterval(() => {
                                    currentMessage = messages[Math.floor(Math.random() * messages.length)]
                                }, 2500)
                                $watch('loading', (value) => {
                                    if (! value) clearInterval(interval)
                                })
                            "
                            x-text="currentMessage"
                        ></p>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="space-y-2">
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div
                                class="h-full w-full bg-gradient-to-r from-primary-600 via-primary-500 to-warning-500"
                                style="animation: progress 2s ease-in-out infinite; transform-origin: left"
                            ></div>
                        </div>
                        <p class="text-xs text-gray-500 italic dark:text-gray-400">This may take a moment...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Custom animation --}}
    <style>
        @keyframes progress {
            0% {
                transform: scaleX(0.3);
                opacity: 0.5;
            }
            50% {
                transform: scaleX(0.7);
                opacity: 1;
            }
            100% {
                transform: scaleX(0.3);
                opacity: 0.5;
            }
        }
    </style>
</div>
