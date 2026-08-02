<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" class="filament-seo-slug-input-wrapper -mt-3">
    <div
        x-data="{
            context: '{{ $getContext() }}', // edit or create
            state: $wire.entangle('{{ $getStatePath() }}'), // current slug value
            statePersisted: '', // slug value received from db
            stateInitial: '', // slug value before modification
            editing: false,
            modified: false,
            initModification: function () {
                this.stateInitial = this.state

                if (! this.statePersisted) {
                    this.statePersisted = this.state
                }

                this.editing = true

                setTimeout(() => $refs.slugInput.focus(), 75)
                ;{{-- $nextTick(() => $refs.slugInput.focus()); --}}
            },
            submitModification: function () {
                if (! this.stateInitial) {
                    this.state = ''
                } else {
                    this.state = this.stateInitial
                }

                $wire.set('{{ $getStatePath() }}', this.state)

                this.detectModification()

                this.editing = false
            },
            cancelModification: function () {
                this.stateInitial = this.state

                this.detectModification()

                this.editing = false
            },
            resetModification: function () {
                this.stateInitial = this.statePersisted

                this.detectModification()
            },
            detectModification: function () {
                this.modified = this.stateInitial !== this.statePersisted
            },
        }"
        x-on:submit.document="modified = false"
    >
        <div {{ $attributes->merge($getExtraAttributes())->class(['filament-forms-text-input-component group flex items-center justify-between gap-4 text-sm']) }}>
            @if ($getReadOnly())
                <span class="flex">
                    <span class="mr-1">{{ $getLabelPrefix() }}</span>
                    <span class="text-gray-400">{{ $getFullBaseUrl() }}</span>
                    <span class="font-semibold text-gray-400">{{ $getState() }}</span>
                </span>

                @if ($getSlugInputUrlVisitLinkVisible())
                    <x-filament::link
                        :href="$getRecordUrl()"
                        target="_blank"
                        size="sm"
                        icon="heroicon-m-arrow-top-right-on-square"
                        icon-position="after"
                    >
                        {{ $getVisitLinkLabel() }}
                    </x-filament::link>
                @endif
            @else
                <span class="@if (! $getState()) flex items-center gap-1 @endif">
                    <span>{{ $getLabelPrefix() }}</span>

                    <span
                        x-text="! editing ? '{{ $getFullBaseUrl() }}' : '{{ $getBasePath() }}'"
                        class="text-gray-400"
                    ></span>

                    <a
                        href="#"
                        role="button"
                        title="{{ __('franken-cms::messages.permalink_action_edit') }}"
                        x-on:click.prevent="initModification()"
                        x-show="! editing"
                        class="hover:text-primary-500 dark:hover:text-primary-400 inline-flex cursor-pointer items-center justify-center gap-1 font-semibold text-gray-400 hover:underline"
                        :class="context !== 'create' && modified
                            ? 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700 px-1 rounded-md'
                            : ''"
                    >
                        <span class="mr-1">{{ $getState() }}</span>

                        @svg('heroicon-m-pencil-square', 'text-primary-600 dark:text-primary-400 h-4 w-4', ['stroke-width' => '2'])

                        <span class="sr-only"> {{ __('franken-cms::messages.permalink_action_edit') }} </span>
                    </a>

                    @if ($getSlugLabelPostfix())
                        <span x-show="! editing" class="ml-0.5 text-gray-400">{{ $getSlugLabelPostfix() }}</span>
                    @endif

                    <span x-show="! editing && context !== 'create' && modified">
                        [{{ __('franken-cms::messages.permalink_status_changed') }}]
                    </span>
                </span>

                <div class="mx-2 flex-1" x-show="editing" style="display: none">
                    <div class="fi-input-wrapper focus-within:ring-primary-600 dark:focus-within:ring-primary-500 fi-fo-text-input flex overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 focus-within:ring-2 dark:bg-white/5 dark:ring-white/20">
                        <input
                            type="text"
                            x-ref="slugInput"
                            x-model="stateInitial"
                            x-bind:disabled="! editing"
                            x-on:keydown.enter="submitModification()"
                            x-on:keydown.escape="cancelModification()"
                            {!! ($autocomplete = $getAutocomplete()) ? "autocomplete=\"{$autocomplete}\"" : null !!}
                            id="{{ $getId() }}"
                            {!! ($placeholder = $getPlaceholder()) ? "placeholder=\"{$placeholder}\"" : null !!}
                            {!! $isRequired() ? 'required' : null !!}
                            {{
                                $getExtraInputAttributeBag()->class([
                                    'fi-input block w-full border-none bg-transparent py-1.5 pe-3 ps-3 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-xs sm:leading-6 dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)]',
                                    'border-danger-600 ring-danger-600' => $errors->has($getStatePath()),
                                ])
                            }}
                        />
                    </div>
                </div>

                <div x-show="editing" class="flex gap-2 space-x-2" style="display: none">
                    <a
                        href="#"
                        role="button"
                        x-on:click.prevent="submitModification()"
                        style="--c-400: var(--primary-400); --c-500: var(--primary-500); --c-600: var(--primary-600)"
                        class="fi-btn fi-btn-size-md fi-btn-color-primary bg-custom-600 hover:bg-custom-500 dark:bg-custom-500 dark:hover:bg-custom-400 focus:ring-custom-500/50 dark:focus:ring-custom-400/50 fi-ac-btn-action relative inline-grid grid-flow-col items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-white shadow-sm transition duration-75 outline-none focus:ring-2 disabled:pointer-events-none disabled:opacity-70"
                    >
                        {{ __('franken-cms::messages.fields.permalink_action_ok') }}
                    </a>

                    <x-filament::link
                        x-show="context === 'edit' && modified"
                        x-on:click.prevent="resetModification()"
                        class="ml-4 cursor-pointer"
                        icon="heroicon-o-arrow-path"
                        color="gray"
                        size="sm"
                        title="{{ __('franken-cms::messages.permalink_action_reset') }}"
                    >
                        <span class="sr-only"> {{ __('franken-cms::messages.permalink_action_reset') }} </span>
                    </x-filament::link>

                    <x-filament::link
                        x-on:click.prevent="cancelModification()"
                        class="cursor-pointer"
                        icon="heroicon-o-x-mark"
                        color="gray"
                        size="sm"
                        title="{{ __('franken-cms::messages.permalink_action_cancel') }}"
                    >
                        <span class="sr-only"> {{ __('franken-cms::messages.permalink_action_cancel') }} </span>
                    </x-filament::link>
                </div>

                <span x-show="context === 'edit'" class="flex items-center space-x-2">
                    @if ($getSlugInputUrlVisitLinkVisible())
                        <template x-if="! editing">
                            <x-filament::link
                                :href="$getRecordUrl()"
                                target="_blank"
                                size="sm"
                                icon="heroicon-m-arrow-top-right-on-square"
                                icon-position="after"
                            >
                                {{ $getVisitLinkLabel() }}
                            </x-filament::link>
                        </template>
                    @endif
                </span>
            @endif
        </div>
    </div>
</x-dynamic-component>
