<div
    x-data="{}"
    @open-ai-modal.window="$dispatch('open-modal', { id: 'ai-generator-modal' })"
>
    <!-- AI Generator Modal Component -->
    <x-filament::modal
        id="ai-generator-modal"
        width="2xl"
        :close-by-clicking-away="false"
    >
    <x-slot name="heading">
        <div class="flex items-center gap-2">
            <x-filament::icon
                icon="heroicon-o-sparkles"
                class="h-6 w-6 text-primary-600 dark:text-primary-400"
            />
            <span>Ask Igor: {{ $promptLabel }}</span>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Current Value --}}
        @if($currentValue)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Current Value
                </label>
                <div class="mt-2 rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    {{ $currentValue }}
                </div>
            </div>
        @endif

        {{-- Generated Result --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Igor's Suggestion
            </label>

            <div class="mt-2 min-h-[120px] rounded-lg border-2 border-dashed px-4 py-3 @if($generatedText) border-primary-300 bg-primary-50 dark:border-primary-700 dark:bg-primary-950 @else border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-900 @endif">
                @if($isGenerating)
                    {{-- Loading State --}}
                    <div class="flex flex-col items-center justify-center py-8">
                        <x-filament::loading-indicator class="h-8 w-8 text-primary-600" />
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                            Igor is thinking...
                        </p>
                    </div>
                @elseif($generatedText)
                    {{-- Generated Content --}}
                    <div class="text-sm text-gray-900 dark:text-gray-100">
                        {{ $generatedText }}
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="flex items-center justify-center py-8">
                        <p class="text-sm text-gray-400 dark:text-gray-500">
                            Click "Generate" to let Igor create content for you
                        </p>
                    </div>
                @endif
            </div>

            {{-- Character Count --}}
            @if($generatedText && $targetMax)
                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="@if($this->getCharacterCountColor() === 'gray') text-gray-500 dark:text-gray-400 @elseif($this->getCharacterCountColor() === 'danger') text-danger-600 dark:text-danger-400 @elseif($this->getCharacterCountColor() === 'success') text-success-600 dark:text-success-400 @elseif($this->getCharacterCountColor() === 'warning') text-warning-600 dark:text-warning-400 @endif">
                        {{ $characterCount }} / {{ $targetMax }} characters
                        @if($characterCount >= $targetMin && $characterCount <= $targetMax)
                            <x-filament::icon
                                icon="heroicon-m-check-circle"
                                class="ml-1 inline h-4 w-4"
                            />
                        @endif
                    </span>

                    @if($targetMin && $targetMax)
                        <span class="text-gray-500 dark:text-gray-400">
                            Target: {{ $targetMin }}-{{ $targetMax }}
                        </span>
                    @endif
                </div>
            @endif
        </div>

        {{-- Error Message --}}
        @if($error)
            <x-filament::section
                color="danger"
                icon="heroicon-o-exclamation-triangle"
            >
                <div class="text-sm text-danger-700 dark:text-danger-300">
                    {{ $error }}
                </div>
            </x-filament::section>
        @endif

    </div>

    <x-slot name="footerActions">
        @if(!$generatedText)
            {{-- Generate Button --}}
            <x-filament::button
                wire:click="generate"
                wire:loading.attr="disabled"
                wire:target="generate"
                color="primary"
                icon="heroicon-o-sparkles"
            >
                <span wire:loading.remove wire:target="generate">Generate</span>
                <span wire:loading wire:target="generate">Generating...</span>
            </x-filament::button>
        @else
            {{-- Regenerate Button --}}
            <x-filament::button
                wire:click="regenerate"
                wire:loading.attr="disabled"
                wire:target="regenerate"
                color="gray"
                icon="heroicon-o-arrow-path"
            >
                <span wire:loading.remove wire:target="regenerate">Try Again</span>
                <span wire:loading wire:target="regenerate">Generating...</span>
            </x-filament::button>

            {{-- Use This Button --}}
            <x-filament::button
                wire:click="useGeneration"
                color="success"
                icon="heroicon-o-check"
            >
                Use This
            </x-filament::button>
        @endif

        {{-- Cancel Button --}}
        <x-filament::button
            wire:click="close"
            color="gray"
            outlined
        >
            Cancel
        </x-filament::button>
    </x-slot>
</x-filament::modal>
</div>
