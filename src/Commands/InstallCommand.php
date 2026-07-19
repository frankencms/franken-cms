<?php

declare(strict_types=1);

namespace FrankenCms\Commands;

use Exception;
use FrankenCms\OgImage\OgImageFeature;
use FrankenCms\Support\IgorMessages;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    public $signature = 'franken-cms:install
                            {--force : Force reinstall even if already installed}
                            {--no-migrations : Skip running migrations}
                            {--panel= : Specify which panel to install to}';

    public $description = 'Install FrankenCMS';

    protected array $detectedPanels = [];

    protected string $packageManager = 'npm';

    public function handle(): int
    {
        $this->newLine();
        $this->renderGradientArt();
        $this->newLine();

        intro('FrankenCMS Installer');

        // Publish config
        if ($this->askToPublishConfig()) {
            $this->publishConfig();
        }

        // Publish migrations
        if ($this->askToPublishMigrations()) {
            $this->publishMigrations();
        }

        // Run migrations
        if (! $this->option('no-migrations')) {
            $this->runMigrations();
        }

        // Detect and select panel
        $selectedPanel = $this->detectAndSelectPanel();

        if (! $selectedPanel) {
            $this->newLine();
            warning('No Filament panels found. Please install Filament first: composer require filament/filament');

            return self::FAILURE;
        }

        // Register plugin if needed
        $alreadyInstalled = ! $this->option('force') && $this->isPluginAlreadyRegistered($selectedPanel);

        if ($alreadyInstalled) {
            info('FrankenCMS plugin is already registered. Continuing with theme and content setup...');
        } elseif (! $this->registerPlugin($selectedPanel)) {
            return self::FAILURE;
        }

        // Setup Filament theme
        $this->setupFilamentTheme($selectedPanel);

        // Comment out default route
        $this->commentOutDefaultRoute();

        // Offer example theme
        $themeInstalled = $this->offerExampleTheme();

        // Rebuild assets if templates were installed
        if ($themeInstalled) {
            $this->buildThemeAssets($this->packageManager);
        }

        // Offer example content (if theme was installed)
        if ($themeInstalled) {
            $this->offerExampleContent();
        }

        // Offer OG image generation setup
        $this->offerOgImageSetup();

        // Success
        $this->showSuccess();

        return self::SUCCESS;
    }

    protected function renderGradientArt(): void
    {
        $lines = explode("\n", IgorMessages::asciiArt('welcome'));

        // Green gradient from dark emerald edges to bright green center
        $colors = [
            '#1a5c2a', // border
            '#1f6b30', // blank
            '#278a3b', // FRANKEN start
            '#2fa845', // |
            '#38c650', // | peak
            '#42e05b', // | brightest
            '#38c650', // |
            '#2fa845', // FRANKEN end
            '#278a3b', // blank
            '#2fa845', // CMS start
            '#38c650', // |
            '#42e05b', // | brightest
            '#38c650', // |
            '#2fa845', // |
            '#278a3b', // CMS end
            '#1f6b30', // blank
            '#1a5c2a', // border
        ];

        foreach ($lines as $index => $line) {
            $color = $colors[$index] ?? $colors[0];
            $this->line("  <fg={$color}>{$line}</>");
        }
    }

    protected function renderSuccessArt(): void
    {
        $lines = explode("\n", IgorMessages::asciiArt('success'));

        // Gold-to-white-to-gold gradient
        $colors = [
            '#c4952a',
            '#d4a832',
            '#e8c44a',
            '#f5e06a',
            '#ffffff',
            '#f5e06a',
            '#e8c44a',
            '#d4a832',
            '#c4952a',
        ];

        $lineCount = count($lines);
        $colorCount = count($colors);

        foreach ($lines as $index => $line) {
            $colorIndex = (int) round($index / max($lineCount - 1, 1) * ($colorCount - 1));
            $color = $colors[$colorIndex] ?? $colors[0];
            $this->line("  <fg={$color}>{$line}</>");
        }
    }

    protected function askToPublishConfig(): bool
    {
        return confirm(
            label: 'Publish configuration file to config/franken-cms.php?',
            default: true,
            hint: 'You can customize package settings if published'
        );
    }

    protected function publishConfig(): void
    {
        spin(
            fn () => $this->callSilently('vendor:publish', [
                '--tag'   => 'franken-cms-config',
                '--force' => true,
            ]),
            'Publishing configuration...'
        );

        info('Configuration published.');
    }

    protected function askToPublishMigrations(): bool
    {
        return confirm(
            label: 'Publish migration files to database/migrations/?',
            default: true,
            hint: 'Required for database setup'
        );
    }

    protected function publishMigrations(): void
    {
        spin(
            fn () => $this->callSilently('vendor:publish', [
                '--tag'   => 'franken-cms-migrations',
                '--force' => true,
            ]),
            'Publishing migrations...'
        );

        info('Migrations published.');
    }

    protected function runMigrations(): void
    {
        $runMigrations = confirm(
            label: 'Run database migrations?',
            default: true,
            hint: 'This will run all FrankenCMS migrations'
        );

        if (! $runMigrations) {
            note('Skipped. Run migrations manually later with: php artisan migrate');

            return;
        }

        try {
            $exitCode = 0;

            spin(
                function () use (&$exitCode) {
                    $exitCode = $this->callSilently('migrate', ['--force' => true]);
                },
                'Running migrations...'
            );

            if ($exitCode === 0) {
                info('Migrations complete.');
            } else {
                $this->handleMigrationError();
            }
        } catch (Exception $e) {
            $this->handleMigrationError($e->getMessage());
        }
    }

    protected function handleMigrationError(?string $errorMessage = null): void
    {
        if ($errorMessage) {
            warning($errorMessage);
        }

        warning('Some migrations may have already been run or encountered issues.');
        note('The installation will continue. You can run migrations manually later with: php artisan migrate');
    }

    protected function detectAndSelectPanel(): ?string
    {
        $panelPath = app_path('Providers/Filament');

        if (! File::isDirectory($panelPath)) {
            return null;
        }

        $panelFiles = File::glob($panelPath . '/*PanelProvider.php');

        if (empty($panelFiles)) {
            return null;
        }

        $this->detectedPanels = collect($panelFiles)
            ->map(fn ($file) => [
                'file'  => $file,
                'name'  => basename($file, '.php'),
                'label' => Str::of(basename($file, 'Provider.php'))->headline()->toString(),
            ])
            ->toArray();

        // If panel specified via option, use that
        if ($panelName = $this->option('panel')) {
            $panel = collect($this->detectedPanels)
                ->firstWhere('name', $panelName . 'PanelProvider');

            if ($panel) {
                info("Using panel: {$panel['label']}");

                return $panel['file'];
            }

            $this->error("Panel '{$panelName}' not found!");

            return null;
        }

        // If only one panel, use it
        if (count($this->detectedPanels) === 1) {
            $panel = $this->detectedPanels[0];

            $installToPanel = confirm(
                label: "Install FrankenCMS to the '{$panel['label']}' panel?",
                default: true
            );

            if (! $installToPanel) {
                return null;
            }

            return $panel['file'];
        }

        // Multiple panels, let user choose
        $choices = collect($this->detectedPanels)
            ->pluck('label', 'file')
            ->toArray();

        return select(
            label: 'Which panel should FrankenCMS be installed to?',
            options: $choices,
            hint: 'Choose a Filament panel'
        );
    }

    protected function isPluginAlreadyRegistered(string $panelFile): bool
    {
        $content = File::get($panelFile);

        return Str::contains($content, 'FrankenCmsPlugin') ||
               Str::contains($content, 'FrankenCms\\FrankenCmsPlugin');
    }

    protected function registerPlugin(string $panelFile): bool
    {
        $backupFile = $panelFile . '.backup';
        File::copy($panelFile, $backupFile);

        try {
            $content = File::get($panelFile);

            // Check if FrankenCmsPlugin is imported
            if (! Str::contains($content, 'use FrankenCms\\FrankenCmsPlugin;')) {
                // Add import after the last 'use' statement
                $content = preg_replace(
                    '/(use\s+[^;]+;)(?![^}]*use\s)/',
                    "$1\nuse FrankenCms\\FrankenCmsPlugin;",
                    $content,
                    1
                );
            }

            // Remove existing plugin registration if force reinstall
            if ($this->option('force')) {
                $content = preg_replace(
                    '/->plugin\s*\(\s*new\s+FrankenCmsPlugin\s*\)/',
                    '',
                    $content
                );
            }

            // Add plugin registration after authMiddleware
            $pluginCode = "->plugin(\n                new FrankenCmsPlugin\n            )";

            // Find authMiddleware and add plugin after it
            if (Str::contains($content, '->authMiddleware(')) {
                $content = preg_replace(
                    '/(->authMiddleware\s*\(\s*\[[^\]]*\]\s*\))/',
                    "$1\n            {$pluginCode}",
                    $content,
                    1
                );
            } else {
                // Fallback: add before the closing semicolon of the return statement
                $content = preg_replace(
                    '/(\n\s*);(\s*\}\s*public\s+function\s+panel)/',
                    "$1\n            {$pluginCode};$2",
                    $content,
                    1
                );
            }

            File::put($panelFile, $content);

            info('Plugin registered successfully.');

            // Clean up backup
            File::delete($backupFile);

            return true;
        } catch (Exception $e) {
            // Restore from backup
            File::copy($backupFile, $panelFile);
            File::delete($backupFile);

            $this->error('Failed to register plugin: ' . $e->getMessage());
            $this->newLine();

            return false;
        }
    }

    protected function setupFilamentTheme(string $panelFile): void
    {
        $panelId = $this->extractPanelId($panelFile);
        $this->packageManager = $this->detectPackageManager();

        // Detect existing theme CSS files
        $existingThemes = File::glob(resource_path('css/filament/*/theme.css'));

        if (empty($existingThemes)) {
            // No theme exists - create one directly (avoid make:filament-theme which has interactive prompts)
            $themePath = $this->createThemeCssFile($panelId);
            $this->registerViteThemeInPanel($panelFile, $panelId);
            $this->installThemeDependencies($this->packageManager);
        } elseif (count($existingThemes) === 1) {
            $themePath = $existingThemes[0];
            info('Using existing theme: ' . Str::after($themePath, base_path() . '/'));
        } else {
            // Multiple themes - let user choose
            $choices = collect($existingThemes)
                ->mapWithKeys(fn (string $path) => [$path => Str::of($path)->afterLast('css/filament/')->beforeLast('/theme.css')->toString()])
                ->toArray();

            $themePath = select(
                label: 'Multiple themes found. Which theme should FrankenCMS be added to?',
                options: $choices,
                hint: 'Select the theme for the panel using FrankenCMS'
            );
        }

        // Append @source directive if not already present
        if (File::exists($themePath)) {
            $this->appendSourceDirective($themePath);
        }

        // Verify vite.config.js has the theme path
        $relativeThemePath = Str::after($themePath, base_path() . '/');
        $this->verifyViteConfig($relativeThemePath);

        // Offer to compile theme assets
        $this->buildThemeAssets($this->packageManager);
    }

    protected function detectPackageManager(): string
    {
        if (File::exists(base_path('bun.lockb')) || File::exists(base_path('bun.lock'))) {
            return 'bun';
        }

        if (File::exists(base_path('yarn.lock'))) {
            return 'yarn';
        }

        if (File::exists(base_path('pnpm-lock.yaml'))) {
            return 'pnpm';
        }

        return 'npm';
    }

    protected function extractPanelId(string $panelFile): string
    {
        $content = File::get($panelFile);

        // Try to match ->id('...') pattern
        if (preg_match('/->id\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches)) {
            return $matches[1];
        }

        // Fallback: derive from filename (AdminPanelProvider.php -> admin)
        return Str::of(basename($panelFile, '.php'))
            ->before('PanelProvider')
            ->lower()
            ->toString();
    }

    protected function createThemeCssFile(string $panelId): string
    {
        $themePath = resource_path("css/filament/{$panelId}/theme.css");

        File::ensureDirectoryExists(dirname($themePath));

        $content = <<<'CSS'
@import '../../../../vendor/filament/filament/resources/css/theme.css';

@source '../../../../app/Filament/**/*';
@source '../../../../resources/views/filament/**/*';
CSS;

        File::put($themePath, $content . "\n");

        info("Created theme CSS at resources/css/filament/{$panelId}/theme.css");

        return $themePath;
    }

    protected function registerViteThemeInPanel(string $panelFile, string $panelId): void
    {
        $content = File::get($panelFile);
        $themeRelativePath = "resources/css/filament/{$panelId}/theme.css";

        if (Str::contains($content, 'viteTheme(')) {
            return;
        }

        // Try to insert after ->path(), then fall back to ->id()
        $pathPattern = '/(->path\s*\(\s*[\'"][^\'"]*[\'"]\s*\))/';
        $idPattern = '/(->id\s*\(\s*[\'"][^\'"]*[\'"]\s*\))/';

        $pattern = preg_match($pathPattern, $content) ? $pathPattern : $idPattern;

        $newContent = preg_replace(
            $pattern,
            "$1\n            ->viteTheme('{$themeRelativePath}')",
            $content,
            1
        );

        if ($newContent !== null && $newContent !== $content) {
            File::put($panelFile, $newContent);
            info('Registered viteTheme() in panel provider.');
        }
    }

    protected function installThemeDependencies(string $pm): void
    {
        $installCommand = match ($pm) {
            'bun'   => 'bun add tailwindcss@latest @tailwindcss/vite --dev',
            'yarn'  => 'yarn add tailwindcss@latest @tailwindcss/vite --dev',
            'pnpm'  => 'pnpm add tailwindcss@latest @tailwindcss/vite --save-dev',
            default => 'npm install tailwindcss@latest @tailwindcss/vite --save-dev',
        };

        $exitCode = 1;

        spin(
            function () use ($installCommand, &$exitCode) {
                exec($installCommand . ' 2>&1', $output, $exitCode);
            },
            'Installing Tailwind CSS dependencies...'
        );

        if ($exitCode === 0) {
            info('Tailwind CSS dependencies installed.');
        } else {
            warning("Could not install Tailwind CSS dependencies automatically. Please run: {$installCommand}");
        }
    }

    protected function buildThemeAssets(string $pm): void
    {
        $buildCommand = "{$pm} run build";

        $runBuild = confirm(
            label: "Compile the theme now with '{$buildCommand}'?",
            default: true,
            hint: 'Required for the admin panel theme to take effect'
        );

        if (! $runBuild) {
            note("Run '{$buildCommand}' to compile the theme when ready.");

            return;
        }

        $exitCode = 1;

        spin(
            function () use ($buildCommand, &$exitCode) {
                exec($buildCommand . ' 2>&1', $output, $exitCode);
            },
            'Compiling theme assets...'
        );

        if ($exitCode === 0) {
            info('Theme assets compiled successfully.');
        } else {
            warning("Build did not complete successfully. Please run '{$buildCommand}' manually.");
        }
    }

    protected function commentOutDefaultRoute(): void
    {
        $webRoutesPath = base_path('routes/web.php');

        if (! File::exists($webRoutesPath)) {
            return;
        }

        $content = File::get($webRoutesPath);

        // Skip if already commented out or not present
        if (! preg_match('/^[^\/\n]*Route::get\s*\(\s*[\'"]\/[\'"]\s*,\s*function/m', $content)) {
            return;
        }

        // Match the full route statement including multi-line closure
        $pattern = '/^([ \t]*)(Route::get\s*\(\s*[\'"]\/[\'"]\s*,\s*function\s*\(\)\s*\{[^}]*\}\s*\)\s*;)/sm';

        if (! preg_match($pattern, $content, $matches)) {
            return;
        }

        $indent = $matches[1];
        $routeBlock = $matches[2];

        // Comment out each line of the route block
        $commentedLines = collect(explode("\n", $routeBlock))
            ->map(fn (string $line) => $line !== '' ? "{$indent}// {$line}" : $line)
            ->implode("\n");

        $replacement = "{$indent}// Commented out by FrankenCMS — the CMS handles all front-end routes.\n{$commentedLines}";

        $newContent = Str::replaceFirst($matches[0], $replacement, $content);

        if ($newContent !== $content) {
            File::put($webRoutesPath, $newContent);
            info('Commented out default welcome route in routes/web.php.');
        }
    }

    protected function appendSourceDirective(string $themePath): void
    {
        $sourceDirective = "@source '../../../../vendor/frankencms/franken-cms/resources/views/**/*.blade.php';";
        $content = File::get($themePath);

        // Check if already present
        if (Str::contains($content, 'frankencms/franken-cms')) {
            note('FrankenCMS @source directive already exists in theme.');

            return;
        }

        // Find the last @source line and insert after it
        $lines = explode("\n", $content);
        $lastSourceIndex = -1;

        foreach ($lines as $index => $line) {
            if (Str::startsWith(trim($line), '@source ')) {
                $lastSourceIndex = $index;
            }
        }

        if ($lastSourceIndex >= 0) {
            // Insert after the last @source line
            array_splice($lines, $lastSourceIndex + 1, 0, [$sourceDirective]);
        } else {
            // No @source lines found - insert after the @import line
            $lastImportIndex = -1;

            foreach ($lines as $index => $line) {
                if (Str::startsWith(trim($line), '@import ')) {
                    $lastImportIndex = $index;
                }
            }

            if ($lastImportIndex >= 0) {
                array_splice($lines, $lastImportIndex + 1, 0, ['', $sourceDirective]);
            } else {
                // Fallback: prepend to file
                array_unshift($lines, $sourceDirective, '');
            }
        }

        File::put($themePath, implode("\n", $lines));

        info('Added FrankenCMS @source directive to theme CSS.');
    }

    protected function verifyViteConfig(string $themePath): void
    {
        $viteConfigPath = base_path('vite.config.js');

        if (! File::exists($viteConfigPath)) {
            return;
        }

        $contents = File::get($viteConfigPath);

        // Already registered
        if (Str::contains($contents, $themePath)) {
            return;
        }

        // Try to add to the input array using the same pattern as Filament's MakeThemeCommand
        $pattern = '/(\binput\s*:\s*\[)([^\]]*?)(\])/s';

        if (! preg_match($pattern, $contents, $matches)) {
            warning("Could not auto-register theme in vite.config.js. Please add '{$themePath}' to the input array manually.");

            return;
        }

        $inputArrayContents = $matches[2];

        // Detect quote style from existing entries
        $quoteStyle = str_contains($inputArrayContents, "'") ? "'" : '"';

        // Find the last quoted string in the array
        if (! preg_match('/^(.*[\'"][^\'"]+[\'"]),?(\s*)$/s', $inputArrayContents, $lastEntryMatch)) {
            return;
        }

        $beforeTrailing = $lastEntryMatch[1];
        $trailingWhitespace = $lastEntryMatch[2];

        $newEntry = "{$quoteStyle}{$themePath}{$quoteStyle}";

        if (str_contains($trailingWhitespace, "\n")) {
            preg_match('/\n(\s+)[\'"]/', $inputArrayContents, $indentMatch);
            $indent = $indentMatch[1] ?? '            ';
            $newInputArrayContents = $beforeTrailing . ",\n{$indent}{$newEntry}," . $trailingWhitespace;
        } else {
            $newInputArrayContents = $beforeTrailing . ", {$newEntry}" . $trailingWhitespace;
        }

        $newContents = preg_replace(
            $pattern,
            '$1' . str_replace(['\\', '$'], ['\\\\', '\\$'], $newInputArrayContents) . '$3',
            $contents,
            1
        );

        if ($newContents !== null && $newContents !== $contents) {
            File::put($viteConfigPath, $newContents);
            info('Added theme CSS to vite.config.js input array.');
        }
    }

    protected function offerExampleTheme(): bool
    {
        $stubsPath = __DIR__ . '/../../stubs/theme';
        $themePath = resource_path('views/theme');

        // Check if example theme stubs exist
        if (! File::isDirectory($stubsPath) || count(File::allFiles($stubsPath)) <= 1) {
            note('Example theme templates are not yet available. They will be added in a future update.');

            return false;
        }

        $installTheme = confirm(
            label: 'Install example theme templates?',
            default: true,
            hint: 'This will copy starter theme files to resources/views/theme/'
        );

        if (! $installTheme) {
            return false;
        }

        spin(
            function () use ($stubsPath, $themePath) {
                File::ensureDirectoryExists($themePath);
                File::copyDirectory($stubsPath, $themePath);
            },
            'Installing example theme...'
        );

        info('Example theme installed to resources/views/theme/');

        return true;
    }

    protected function offerExampleContent(): void
    {
        $generateContent = confirm(
            label: 'Generate example content (pages, posts, categories)?',
            default: true,
            hint: 'This helps you see how FrankenCMS works with real data'
        );

        if (! $generateContent) {
            return;
        }

        spin(
            fn () => $this->callSilently('db:seed', [
                '--class' => 'FrankenCms\\Database\\Seeders\\ExampleContentSeeder',
            ]),
            'Generating example content...'
        );

        info(
            "Example content created:\n" .
            "   Pages: Home, About, Blog, Contact\n" .
            "   Posts: 3 example blog posts\n" .
            "   Categories & Tags\n" .
            '   Main Navigation Menu'
        );
    }

    protected function offerOgImageSetup(): void
    {
        if (OgImageFeature::isInstalled()) {
            info(IgorMessages::installMessage('og_image_already_installed', 'igor'));

            return;
        }

        $installOgImage = confirm(
            label: IgorMessages::installMessage('og_image_offer', 'igor'),
            default: true,
            hint: 'Recommended — needs Chrome/Node on the server, or Cloudflare credentials'
        );

        if (! $installOgImage) {
            note(IgorMessages::installMessage('og_image_skip', 'igor'));

            return;
        }

        $exitCode = 1;

        spin(
            function () use (&$exitCode) {
                exec('composer require spatie/laravel-og-image 2>&1', $output, $exitCode);
            },
            IgorMessages::installMessage('og_image_installing', 'igor')
        );

        if ($exitCode !== 0) {
            warning('Could not install spatie/laravel-og-image automatically. Please run: composer require spatie/laravel-og-image');

            return;
        }

        info('spatie/laravel-og-image installed.');

        // Publish in a fresh process — the current process booted before the
        // package was required, so its class map and provider registration
        // are stale (package:discover has not run in-process). Shelling out
        // gives us a fresh `php artisan` boot that can see the new provider.
        $publishExitCode = 1;

        spin(
            function () use (&$publishExitCode) {
                exec('php artisan vendor:publish --tag=og-image-config --force 2>&1', $publishOutput, $publishExitCode);
            },
            'Publishing OG image configuration...'
        );

        if ($publishExitCode !== 0) {
            warning('Could not publish the OG image config automatically. Please run: php artisan vendor:publish --tag=og-image-config');

            return;
        }

        info(IgorMessages::installMessage('og_image_configured', 'igor'));

        note(IgorMessages::ogImageFollowUp());
    }

    protected function showSuccess(): void
    {
        $this->newLine();
        $this->renderSuccessArt();
        $this->newLine();

        outro('FrankenCMS installed successfully!');

        $this->newLine();
        $this->line('  <options=bold>Next steps:</>');
        $this->line('    1. Visit <fg=cyan>/admin</> to access your Filament panel');
        $this->line('    2. Configure settings in <fg=cyan>config/franken-cms.php</>');
        $this->line('    3. Check out CMS Settings in your admin panel');
        $this->line('    4. Create your first post or page!');
        $this->newLine();
        $this->line('  Documentation: <fg=cyan>https://frankencms.com/docs</>');
        $this->line('  Community:     <fg=cyan>https://github.com/frankencms/franken-cms</>');
        $this->newLine();
    }
}
