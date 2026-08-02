@props([
    'title'    => __('No featured image'),
    'subtitle' => __('Upload an image to see a preview here.'),
    'icon'     => 'heroicon-o-photo', // expects a Blade UI Icons name if available; falls back to SVG below
])

<div {{
    $attributes->class([
        'flex items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50/50 p-6',
        'dark:border-gray-700 dark:bg-gray-900/30',
    ])
}}>
    <div class="flex flex-col items-center text-center">
        @if (class_exists(\BladeUIKit\Icons\Factory::class))
            <x-dynamic-component :component="$icon" class="size-10 text-gray-400 dark:text-gray-500" />
        @else
            <svg class="h-10 w-10 text-gray-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M3.75 5.25A2.25 2.25 0 0 1 6 3h12a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 18 21H6a2.25 2.25 0 0 1-2.25-2.25V5.25Z" stroke="currentColor" stroke-width="1.5" />
                <path d="M7.5 14.25l2.75-2.75a1.5 1.5 0 0 1 2.12 0l4.13 4.13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                <path d="M14.25 9.375a1.125 1.125 0 1 0 2.25 0 1.125 1.125 0 0 0-2.25 0Z" fill="currentColor" />
            </svg>
        @endif

        <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $title }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
    </div>
</div>
