<div class="rounded-lg border border-warning-200 bg-warning-50 p-4 dark:border-warning-500/20 dark:bg-warning-500/10">
    <div class="flex items-start gap-3">
        <x-filament::icon
            icon="heroicon-o-exclamation-triangle"
            class="mt-0.5 h-5 w-5 flex-shrink-0 text-warning-600 dark:text-warning-400"
        />
        <div class="flex-1">
            <h4 class="text-sm font-medium text-warning-900 dark:text-warning-400">Overwrite the current creation?</h4>
            <p class="mt-1 text-sm text-warning-700 dark:text-warning-500">
                You possess near
                <strong>{{ $wordCount }}</strong>
                words of prior text, a modest foundation. Yet beware! Igor’s latest creation shall sweep it all away,
                replacing every line within the editor with his freshly animated prose!
            </p>

            @if ($preview)
                <div
                    class="mt-3 rounded border border-warning-200 bg-warning-100 p-3 font-mono text-xs text-warning-800 dark:border-warning-800 dark:bg-warning-950/50 dark:text-warning-300"
                >
                    <div class="mb-1 text-xs font-semibold text-warning-700 dark:text-warning-400">
                        Current content preview:
                    </div>
                    {{ $preview }}...
                </div>
            @endif
        </div>
    </div>
</div>
