<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-950/50 dark:bg-gray-950/75 transition-opacity"></div>

            <!-- Modal panel -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-xl bg-white shadow-xl dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10">

                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
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

                    <!-- Progress indicator -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            @foreach([1, 2, 3, 4] as $stepNumber)
                                @php
                                    // Skip step 2 if no existing content
                                    if ($stepNumber === 2 && !$this->hasExistingContent()) {
                                        continue;
                                    }
                                    $stepLabels = [
                                        1 => 'Context',
                                        2 => 'Confirm',
                                        3 => 'Generate',
                                        4 => 'Review',
                                    ];
                                @endphp
                                <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                                    <div class="flex flex-col items-center">
                                        <div @class([
                                            'flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                                            'bg-primary-600 text-white' => $step >= $stepNumber,
                                            'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400' => $step < $stepNumber,
                                        ])>
                                            {{ $stepNumber > 2 && !$this->hasExistingContent() ? $stepNumber - 1 : $stepNumber }}
                                        </div>
                                        <span class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                            {{ $stepLabels[$stepNumber] }}
                                        </span>
                                    </div>
                                    @if(!$loop->last)
                                        <div @class([
                                            'h-0.5 flex-1 mx-2',
                                            'bg-primary-600' => $step > $stepNumber,
                                            'bg-gray-200 dark:bg-gray-700' => $step <= $stepNumber,
                                        ])></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-6">
                        <!-- Step 1: Gather Context -->
                        @if ($step === 1)
                            <div class="space-y-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Help Igor understand what kind of blog post you want to create. Be specific about your topic and target audience.
                                </p>

                                <!-- Focus -->
                                <div>
                                    <label for="focus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Topic / Focus <span class="text-danger-600">*</span>
                                    </label>
                                    <textarea
                                        wire:model="focus"
                                        id="focus"
                                        rows="2"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                        placeholder='e.g., "10 productivity tips for remote workers"'
                                    ></textarea>
                                    @error('focus')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">The main topic or angle of your blog post</p>
                                </div>

                                <!-- Audience -->
                                <div>
                                    <label for="audience" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Target Audience <span class="text-danger-600">*</span>
                                    </label>
                                    <input
                                        wire:model="audience"
                                        type="text"
                                        id="audience"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                        placeholder='e.g., "Busy professionals working from home"'
                                    />
                                    @error('audience')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">Who is this blog post for?</p>
                                </div>

                                <!-- Notes -->
                                <div>
                                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Key Points or Notes <span class="text-gray-400">(optional)</span>
                                    </label>
                                    <textarea
                                        wire:model="notes"
                                        id="notes"
                                        rows="4"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                        placeholder="• Focus on practical tools&#10;• Include time-saving strategies&#10;• Mention async communication"
                                    ></textarea>
                                    <p class="mt-1 text-xs text-gray-500">Specific points, keywords, or ideas to include</p>
                                </div>

                                @if($error)
                                    <div class="rounded-lg bg-danger-50 dark:bg-danger-500/10 p-4">
                                        <div class="flex">
                                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-danger-400" />
                                            <div class="ml-3">
                                                <p class="text-sm text-danger-800 dark:text-danger-400">{{ $error }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Step 2: Confirmation -->
                        @if ($step === 2)
                            <div class="space-y-4">
                                <div class="flex items-start gap-3 rounded-lg bg-warning-50 dark:bg-warning-500/10 p-4">
                                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-warning-600 dark:text-warning-400 mt-0.5" />
                                    <div>
                                        <h4 class="text-sm font-medium text-warning-900 dark:text-warning-400">
                                            Replace Existing Content?
                                        </h4>
                                        <p class="mt-1 text-sm text-warning-700 dark:text-warning-500">
                                            You have approximately {{ $this->getContentWordCount() }} words of existing content.
                                            Generating a new blog post will replace everything in the editor.
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Current content preview:
                                    </label>
                                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4 max-h-40 overflow-y-auto">
                                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-6">
                                            {{ \Illuminate\Support\Str::limit($this->extractTextFromContent($currentContent), 300) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2">
                                    <input
                                        wire:model="confirmReplace"
                                        type="checkbox"
                                        id="confirmReplace"
                                        class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600"
                                    />
                                    <label for="confirmReplace" class="text-sm text-gray-700 dark:text-gray-300">
                                        I understand this will replace my current content
                                    </label>
                                </div>
                                @error('confirmReplace')
                                    <p class="text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <!-- Step 3: Generating -->
                        @if ($step === 3)
                            <div class="py-12 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-500/10 mb-4">
                                    <svg class="animate-spin h-8 w-8 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                    Igor is writing your blog post...
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                                    Creating 800-1,200 words of SEO-optimized content for: "{{ $focus }}"
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-4">
                                    This may take 30-60 seconds...
                                </p>
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
                        @if ($step === 1)
                            <button
                                type="button"
                                wire:click="close"
                                class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                wire:click="nextStep"
                                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                            >
                                Next
                                <x-filament::icon icon="heroicon-o-arrow-right" class="h-4 w-4" />
                            </button>
                        @elseif ($step === 2)
                            <button
                                type="button"
                                wire:click="previousStep"
                                class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                            >
                                <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                                Back
                            </button>
                            <button
                                type="button"
                                wire:click="nextStep"
                                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                            >
                                Generate Blog Post
                            </button>
                        @elseif ($step === 3)
                            <!-- No buttons while generating -->
                        @elseif ($step === 4)
                            <button
                                type="button"
                                wire:click="regenerate"
                                class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                            >
                                Regenerate
                            </button>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    wire:click="close"
                                    class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    wire:click="insertContent"
                                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                                >
                                    Insert into Editor
                                </button>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>
