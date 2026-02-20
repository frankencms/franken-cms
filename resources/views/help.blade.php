@php
    $icon = match ($type) {
        'info' => 'heroicon-o-information-circle',
        'warning' => 'heroicon-o-exclamation',
        'error' => 'heroicon-o-x-circle',
        'success' => 'heroicon-o-check-circle',
        default => 'heroicon-o-question-mark-circle',
    };

    $colors = match ($type) {
        'info' => 'border-blue-500 bg-blue-100 text-blue-700',
        'warning' => 'border-yellow-500 bg-yellow-100 text-yellow-700',
        'error' => 'border-red-500 bg-red-100 text-red-700',
        'success' => 'border-green-500 bg-green-100 text-green-700',
        default => 'border-gray-500 bg-gray-100 text-gray-700',
    };
@endphp

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta
            name="viewport"
            content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"
        />
        <meta http-equiv="X-UA-Compatible" content="ie=edge" />
        <title>Help</title>
        <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    </head>
    <body>
        <div class="mx-auto mt-12 max-w-3xl px-2 py-12">
            <div class="{{ $colors }} flex items-start rounded-md border-l-4 p-4 shadow-sm">
                <svg
                    class="mt-0.5 h-6 w-6 flex-shrink-0"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    @if ($type === 'info')
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M12 8v.01M12 22C6.48 22 2 12S6.48 2 12S10 4.48 10 10-4.48 10-10 10z"
                        />
                    @elseif ($type === 'warning')
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"
                        />
                    @elseif ($type === 'error')
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    @elseif ($type === 'success')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    @else
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-3 3v-6m0 9a9 9 0 100-18 9 9 0 000 18z"
                        />
                    @endif
                </svg>

                <div class="ml-3">
                    <p class="text-sm font-medium">{{ $message }}</p>
                </div>
            </div>
        </div>
    </body>
</html>
