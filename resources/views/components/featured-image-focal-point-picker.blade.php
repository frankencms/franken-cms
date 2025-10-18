<div class="space-y-4">
    <div class="flex items-center justify-between">
        <label class="text-sm font-medium text-gray-950 dark:text-white">
            {{ __('Focal Point') }}
        </label>
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Click on the preview to set focal point
        </div>
    </div>

    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('featured-image-focal-picker', 'frankencms/franken-cms') }}"
        x-data="featuredImageFocalPicker(
            @if ($existingImageSrc ?? null)'{{ $existingImageSrc }}'@else null @endif,
            {{ $existingFocalX ?? 50 }},
            {{ $existingFocalY ?? 50 }},
            '{{ $statePaths['focal_x'] ?? 'data.featured_image_focal_x' }}',
            '{{ $statePaths['focal_y'] ?? 'data.featured_image_focal_y' }}'
        )"
        class="space-y-4"
    >
        <!-- Preview Area -->
        <div
            class="select-none relative bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden border-2 border-dashed border-gray-300 dark:border-gray-600"
{{--            style="min-height: 200px; max-height: 300px;"--}}
            x-ref="previewArea"
            @click="setFocalPoint($event)"
        >
            <!-- Placeholder when no image -->
            <div x-show="!imagePreview" class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-sm">Upload an image above to set focal point</p>
                </div>
            </div>

            <!-- Image preview -->
            <div x-show="imagePreview" class="relative w-full h-full">
                <img
                    x-ref="previewImage"
                    :src="imagePreview"
                    alt="Preview"
                    class="w-full h-full object-cover"
                />

                <!-- Focal point indicator -->
                <div
                    class="absolute w-4 h-4 bg-red-500 border-2 border-white rounded-full transform -translate-x-1/2 -translate-y-1/2 shadow-lg"
                    :style="`left: ${focalX}%; top: ${focalY}%;`"
                ></div>

                <!-- Crosshair lines for better precision -->
                <div
                    class="absolute border-l border-red-300 opacity-50 pointer-events-none"
                    :style="`left: ${focalX}%; top: 0; height: 100%;`"
                ></div>
                <div
                    class="absolute border-t border-red-300 opacity-50 pointer-events-none"
                    :style="`top: ${focalY}%; left: 0; width: 100%;`"
                ></div>
            </div>
        </div>

        <!-- Coordinate inputs (display only) -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    X Position (%)
                </label>
                <input
                    type="number"
                    min="0"
                    max="100"
                    step="0.1"
                    x-model="focalX"
                    @input="updateFocalPoint"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:bg-gray-900 dark:text-white text-sm"
                />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Y Position (%)
                </label>
                <input
                    type="number"
                    min="0"
                    max="100"
                    step="0.1"
                    x-model="focalY"
                    @input="updateFocalPoint"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:bg-gray-900 dark:text-white text-sm"
                />
            </div>
        </div>

        <!-- Info and Reset button -->
        <div class="flex items-center justify-between">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Current: <span x-text="`${Math.round(focalX * 10) / 10}%, ${Math.round(focalY * 10) / 10}%`"></span>
            </div>
            <button
                type="button"
                @click="resetFocalPoint"
                class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium"
            >
                Reset to center
            </button>
        </div>
    </div>
</div>
