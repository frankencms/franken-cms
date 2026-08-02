<div class="border-warning-200 bg-warning-50 dark:border-warning-500/20 dark:bg-warning-500/10 rounded-lg border p-4">
    <div class="flex items-start gap-3">
        <x-filament::icon
            icon="heroicon-o-exclamation-triangle"
            class="text-warning-600 dark:text-warning-400 mt-0.5 h-5 w-5 flex-shrink-0"
        />
        <div class="flex-1">
            <h4 class="text-warning-900 dark:text-warning-400 text-sm font-medium">Overwrite the current creation?</h4>
            <p class="text-warning-700 dark:text-warning-500 mt-1 text-sm">
                You possess near
                <strong>{{ $wordCount }}</strong>
                words of prior text, a modest foundation. Yet beware! Igor’s latest creation shall sweep it all away,
                replacing every line within the editor with his freshly animated prose!
            </p>

            @if ($preview)
                <div class="border-warning-200 bg-warning-100 text-warning-800 dark:border-warning-800 dark:bg-warning-950/50 dark:text-warning-300 mt-3 rounded border p-3 font-mono text-xs">
                    <div class="text-warning-700 dark:text-warning-400 mb-1 text-xs font-semibold">
                        Current content preview:
                    </div>
                    {{ $preview }}...
                </div>
            @endif
        </div>
    </div>
</div>
