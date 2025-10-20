<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-950/50 dark:bg-gray-950/75 transition-opacity"></div>

            <!-- Modal panel -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl rounded-xl bg-white shadow-xl dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10">

                    <!-- Header -->
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between px-6 py-4">
                            <div class="flex items-center gap-3">
                                <x-filament::icon
                                    icon="heroicon-o-sparkles"
                                    class="h-6 w-6 text-primary-600 dark:text-primary-400"
                                />
                                <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                                    Generate Blog Post with Igor
                                </h3>
                            </div>
                            <button
                                type="button"
                                wire:click="close"
                                class="rounded-lg p-1.5 text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                            >
                                <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                            </button>
                        </div>

                        {{-- Step Indicator --}}
                        @if($step <= 2)
                            <div class="px-6 pb-4">
                                <div class="flex items-center gap-2">
                                    @php
                                        $steps = [
                                            1 => 'Context',
                                            2 => 'Confirm',
                                            3 => 'Generate',
                                            4 => 'Review'
                                        ];
                                        $totalSteps = $this->hasExistingContent() ? 4 : 3;
                                    @endphp

                                    @foreach($steps as $num => $label)
                                        @if($num === 2 && !$this->hasExistingContent())
                                            @continue
                                        @endif

                                        <div class="flex items-center">
                                            <div @class([
                                                'flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                                                'bg-primary-600 text-white' => $step >= $num,
                                                'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400' => $step < $num,
                                            ])>
                                                {{ $num > 2 && !$this->hasExistingContent() ? $num - 1 : $num }}
                                            </div>
                                            @if(!$loop->last && !($num === 1 && !$this->hasExistingContent() && $loop->iteration === 1))
                                                <div @class([
                                                    'h-0.5 w-12 mx-2',
                                                    'bg-primary-600' => $step > $num,
                                                    'bg-gray-200 dark:bg-gray-700' => $step <= $num,
                                                ])></div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    @if($step === 1)
                                        Help Igor understand what kind of blog post you want to create.
                                    @elseif($step === 2)
                                        Please confirm you want to replace existing content.
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-6">
                        @if ($step === 1 || $step === 2)
                            {{-- Render Filament form for steps 1-2 --}}
                            <form wire:submit="nextStep">
                                {{ $this->form }}
                            </form>
                        @endif

                        <!-- Step 3: Generating -->
                        @if ($step === 3)
                            <div wire:init="generateBlogPost" class="space-y-4">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-500/10">
                                        <svg class="animate-spin h-5 w-5 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                            Igor is writing your blog post...
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $focus }}
                                        </p>
                                    </div>
                                </div>

                                @if($generatedContent)
                                    <div wire:stream="generatedContent" class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 max-h-96 overflow-y-auto prose prose-sm dark:prose-invert max-w-none">
                                        {!! \Illuminate\Support\Str::markdown($generatedContent) !!}
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Step 4: Review -->
                        @if ($step === 4 && $generatedContent)
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Generated Blog Post
                                    </h4>
                                    <span class="text-xs text-gray-500">
                                        {{ str_word_count(strip_tags($generatedContent)) }} words
                                    </span>
                                </div>

                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 max-h-96 overflow-y-auto prose prose-sm dark:prose-invert max-w-none">
                                    {!! \Illuminate\Support\Str::markdown($generatedContent) !!}
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                        @if ($step === 1 || $step === 2)
                            <x-filament::button
                                color="gray"
                                wire:click="close"
                            >
                                Cancel
                            </x-filament::button>

                            <div class="flex items-center gap-2">
                                @if($step === 2)
                                    <x-filament::button
                                        color="gray"
                                        wire:click="previousStep"
                                        icon="heroicon-o-arrow-left"
                                        icon-position="before"
                                    >
                                        Back
                                    </x-filament::button>
                                @endif

                                <x-filament::button
                                    wire:click="nextStep"
                                    icon="heroicon-o-arrow-right"
                                    icon-position="after"
                                >
                                    {{ $step === 2 ? 'Generate Blog Post' : 'Next' }}
                                </x-filament::button>
                            </div>
                        @elseif ($step === 3)
                            <!-- No buttons while generating -->
                        @elseif ($step === 4)
                            <x-filament::button
                                color="gray"
                                wire:click="regenerate"
                            >
                                Regenerate
                            </x-filament::button>

                            <div class="flex items-center gap-2">
                                <x-filament::button
                                    color="gray"
                                    wire:click="close"
                                >
                                    Cancel
                                </x-filament::button>
                                <x-filament::button
                                    wire:click="insertContent"
                                >
                                    Insert into Editor
                                </x-filament::button>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>
