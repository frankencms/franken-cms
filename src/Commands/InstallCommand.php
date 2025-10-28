<?php

declare(strict_types=1);

namespace FrankenCms\Commands;

use Exception;
use FrankenCms\Support\IgorMessages;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class InstallCommand extends Command
{
    public $signature = 'franken-cms:install
                            {--force : Force reinstall even if already installed}
                            {--no-migrations : Skip running migrations}
                            {--panel= : Specify which panel to install to}';

    public $description = 'Install FrankenCMS with theatrical flair';

    protected array $detectedPanels = [];

    public function handle(): int
    {
        // Welcome screen
        $this->showWelcome();

        // Step 1: Publish config (ask first)
        if ($this->askToPublishConfig()) {
            $this->publishConfig();
        }

        // Step 2: Publish migrations (ask first)
        if ($this->askToPublishMigrations()) {
            $this->publishMigrations();
        }

        // Step 3: Run migrations
        if (! $this->option('no-migrations')) {
            $this->runMigrations();
        }

        // Step 4: Detect and select panel
        $selectedPanel = $this->detectAndSelectPanel();

        if (! $selectedPanel) {
            $this->igorSays('No Filament panels found, Master... 😕');
            $this->doctorSays('We need a panel to bring our creation to life!');
            $this->newLine();
            $this->warn('Please install Filament first: composer require filament/filament');

            return self::FAILURE;
        }

        // Step 5: Check if already installed
        if (! $this->option('force') && $this->isPluginAlreadyRegistered($selectedPanel)) {
            if (! $this->handleExistingInstallation()) {
                return self::SUCCESS;
            }
        }

        // Step 6: Register plugin
        if (! $this->registerPlugin($selectedPanel)) {
            return self::FAILURE;
        }

        // Step 7: Offer example theme
        $this->offerExampleTheme();

        // Step 8: Success!
        $this->showSuccess();

        return self::SUCCESS;
    }

    protected function showWelcome(): void
    {
        $this->newLine();
        $this->line(IgorMessages::asciiArt('welcome'));
        $this->newLine();
        $this->dramaticPause(800);

        $this->igorSays(IgorMessages::installMessage('welcome', 'igor'));
        $this->dramaticPause(500);
        $this->doctorSays(IgorMessages::installMessage('welcome', 'doctor'));
        $this->dramaticPause(500);
    }

    protected function askToPublishConfig(): bool
    {
        $this->igorSays(IgorMessages::installMessage('asking_config', 'igor'));
        $this->dramaticPause(300);
        $this->doctorSays(IgorMessages::installMessage('asking_config', 'doctor'));
        $this->dramaticPause(400);

        $publishConfig = confirm(
            label: '📜 Publish configuration file to config/franken-cms.php?',
            default: true,
            hint: 'You can customize package settings if published'
        );

        if (! $publishConfig) {
            $this->igorSays(IgorMessages::installMessage('skip_config', 'igor'));
            $this->dramaticPause(300);
            $this->doctorSays(IgorMessages::installMessage('skip_config', 'doctor'));
            $this->dramaticPause(400);
        }

        return $publishConfig;
    }

    protected function publishConfig(): void
    {
        $this->igorSays(IgorMessages::installMessage('publishing_config', 'igor'));
        $this->dramaticPause(300);

        spin(
            fn () => $this->callSilently('vendor:publish', [
                '--tag'   => 'franken-cms-config',
                '--force' => true,
            ]),
            'Publishing configuration files...'
        );

        $this->dramaticPause(300);
        $this->doctorSays(IgorMessages::installMessage('publishing_config', 'doctor'));
        $this->dramaticPause(400);
    }

    protected function askToPublishMigrations(): bool
    {
        $this->igorSays(IgorMessages::installMessage('asking_migrations', 'igor'));
        $this->dramaticPause(300);
        $this->doctorSays(IgorMessages::installMessage('asking_migrations', 'doctor'));
        $this->dramaticPause(400);

        $publishMigrations = confirm(
            label: '🔧 Publish migration files to database/migrations/?',
            default: true,
            hint: 'Required for database setup'
        );

        if (! $publishMigrations) {
            $this->igorSays(IgorMessages::installMessage('skip_migrations', 'igor'));
            $this->dramaticPause(300);
            $this->doctorSays(IgorMessages::installMessage('skip_migrations', 'doctor'));
            $this->dramaticPause(400);
        }

        return $publishMigrations;
    }

    protected function publishMigrations(): void
    {
        $this->igorSays(IgorMessages::installMessage('publishing_migrations', 'igor'));
        $this->dramaticPause(300);

        spin(
            fn () => $this->callSilently('vendor:publish', [
                '--tag'   => 'franken-cms-migrations',
                '--force' => true,
            ]),
            'Publishing migration files...'
        );

        $this->dramaticPause(300);
        $this->doctorSays(IgorMessages::installMessage('publishing_migrations', 'doctor'));
        $this->dramaticPause(400);
    }

    protected function runMigrations(): void
    {
        $runMigrations = confirm(
            label: '⚡ Shall we bring the database to LIFE, Master?',
            default: true,
            hint: 'This will run all FrankenCMS migrations'
        );

        if (! $runMigrations) {
            $this->igorSays('As you wish, Master... Igor will wait...');
            $this->dramaticPause(300);

            return;
        }

        $this->igorSays(IgorMessages::installMessage('running_migrations', 'igor'));
        $this->dramaticPause(300);
        $this->doctorSays(IgorMessages::installMessage('running_migrations', 'doctor'));
        $this->dramaticPause(400);

        try {
            $exitCode = 0;

            spin(
                function () use (&$exitCode) {
                    $exitCode = $this->callSilently('migrate', ['--force' => true]);
                },
                'Running migrations...'
            );

            if ($exitCode === 0) {
                $this->dramaticPause(300);
                $this->igorSays(IgorMessages::installMessage('migration_success', 'igor'));
                $this->dramaticPause(300);
                $this->doctorSays(IgorMessages::installMessage('migration_success', 'doctor'));
                $this->dramaticPause(400);
            } else {
                $this->handleMigrationError();
            }
        } catch (Exception $e) {
            $this->handleMigrationError($e->getMessage());
        }
    }

    protected function handleMigrationError(?string $errorMessage = null): void
    {
        $this->dramaticPause(300);
        $this->igorSays(IgorMessages::installMessage('migration_error', 'igor'));
        $this->dramaticPause(400);

        if ($errorMessage) {
            note(
                "⚠️  {$errorMessage}\n\n" .
                'This usually means migrations already exist or there is a database connection issue.',
                'Migration Warning'
            );
        } else {
            note(
                "⚠️  Some migrations may have already been run or encountered issues.\n\n" .
                'This is often normal if you are re-installing or the database is already set up.',
                'Migration Warning'
            );
        }

        $this->dramaticPause(300);
        $this->doctorSays(IgorMessages::installMessage('migration_error', 'doctor'));
        $this->dramaticPause(400);

        note(
            'The installation will continue. You can run migrations manually later with: php artisan migrate',
            'Continuing...'
        );
    }

    protected function detectAndSelectPanel(): ?string
    {
        $this->igorSays(IgorMessages::installMessage('detecting_panels', 'igor'));
        $this->dramaticPause(300);

        // Detect panel providers
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

        $this->doctorSays(IgorMessages::installMessage('detecting_panels', 'doctor'));
        $this->dramaticPause(500);

        // If panel specified via option, use that
        if ($panelName = $this->option('panel')) {
            $panel = collect($this->detectedPanels)
                ->firstWhere('name', $panelName . 'PanelProvider');

            if ($panel) {
                note("🎯 Using panel: {$panel['label']}", 'Selected');
                $this->dramaticPause(300);

                return $panel['file'];
            }

            $this->error("Panel '{$panelName}' not found!");

            return null;
        }

        // If only one panel, use it
        if (count($this->detectedPanels) === 1) {
            $panel = $this->detectedPanels[0];

            $installToPanel = confirm(
                label: "🎯 Install FrankenCMS to the '{$panel['label']}' panel?",
                default: true
            );

            return $installToPanel ? $panel['file'] : null;
        }

        // Multiple panels, let user choose
        $choices = collect($this->detectedPanels)
            ->pluck('label', 'file')
            ->toArray();

        $selectedFile = select(
            label: '🧪 Which laboratory shall host our creation?',
            options: $choices,
            hint: 'Choose a Filament panel to install FrankenCMS'
        );

        return $selectedFile;
    }

    protected function isPluginAlreadyRegistered(string $panelFile): bool
    {
        $content = File::get($panelFile);

        // Check if FrankenCmsPlugin is already registered
        return Str::contains($content, 'FrankenCmsPlugin') ||
               Str::contains($content, 'FrankenCms\\FrankenCmsPlugin');
    }

    protected function handleExistingInstallation(): bool
    {
        $this->igorSays(IgorMessages::installMessage('already_installed', 'igor'));
        $this->dramaticPause(400);
        $this->doctorSays(IgorMessages::installMessage('already_installed', 'doctor'));
        $this->dramaticPause(500);

        $choice = select(
            label: '⚠️  What would you like to do?',
            options: [
                'skip'  => 'Skip - It\'s already working',
                'force' => 'Force reinstall - Replace the existing registration',
                'abort' => 'Abort - Stop the installation',
            ],
            default: 'skip'
        );

        return match ($choice) {
            'force' => true,
            'abort' => false,
            default => false,
        };
    }

    protected function registerPlugin(string $panelFile): bool
    {
        $this->igorSays(IgorMessages::installMessage('registering_plugin', 'igor'));
        $this->dramaticPause(400);

        // Create backup
        $backupFile = $panelFile . '.backup';
        File::copy($panelFile, $backupFile);
        $this->igorSays(IgorMessages::installMessage('backup_created', 'igor'));
        $this->dramaticPause(300);

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

            $this->dramaticPause(300);
            $this->doctorSays(IgorMessages::installMessage('registering_plugin', 'doctor'));
            $this->dramaticPause(300);

            note('✅ Plugin registered successfully!', 'Success');

            // Clean up backup
            File::delete($backupFile);

            return true;
        } catch (Exception $e) {
            // Restore from backup
            File::copy($backupFile, $panelFile);
            File::delete($backupFile);

            $this->igorSays(IgorMessages::installMessage('error', 'igor'));
            $this->doctorSays(IgorMessages::installMessage('error', 'doctor'));
            $this->error('Failed to register plugin: ' . $e->getMessage());
            $this->newLine();

            return false;
        }
    }

    protected function offerExampleTheme(): void
    {
        $this->igorSays(IgorMessages::installMessage('theme_install', 'igor'));
        $this->dramaticPause(300);

        $stubsPath = __DIR__ . '/../../stubs/theme';
        $themePath = resource_path('views/theme');

        // Check if example theme stubs exist
        if (! File::isDirectory($stubsPath) || count(File::allFiles($stubsPath)) <= 1) {
            note(
                '📝 Example theme templates are not yet available. They will be added in a future update!',
                'Coming Soon'
            );
            $this->dramaticPause(300);

            return;
        }

        $installTheme = confirm(
            label: '📝 Install example theme templates?',
            default: false,
            hint: 'This will copy starter theme files to resources/views/theme/'
        );

        if ($installTheme) {
            spin(
                function () use ($stubsPath, $themePath) {
                    File::ensureDirectoryExists($themePath);
                    File::copyDirectory($stubsPath, $themePath);
                },
                'Installing example theme...'
            );

            note('✅ Example theme installed to resources/views/theme/', 'Success');
            $this->dramaticPause(300);
        }
    }

    protected function showSuccess(): void
    {
        $this->dramaticPause(800);
        $this->newLine();
        $this->line(IgorMessages::asciiArt('success'));
        $this->newLine();
        $this->dramaticPause(500);

        $this->igorSays(IgorMessages::installMessage('success', 'igor'));
        $this->dramaticPause(600);
        $this->doctorSays(IgorMessages::installMessage('success', 'doctor'));
        $this->dramaticPause(800);

        note(
            "1. Visit /admin to access your Filament panel\n" .
            "2. Configure settings at config/franken-cms.php\n" .
            "3. Check out the CMS Settings in your admin panel\n" .
            '4. Create your first post or page!',
            '🎉 Next Steps'
        );

        $this->newLine();
        $this->line('  📚 Documentation: <fg=cyan>https://frankencms.com/docs</>');
        $this->line('  💬 Community: <fg=cyan>https://github.com/frankencms/franken-cms</>');
        $this->newLine(2);
    }

    protected function igorSays(string $message, bool $typewriter = true): void
    {
        if ($typewriter) {
            $this->typewriterDialogue('🧟 Igor', $message, 'cyan');
        } else {
            note($message, 'Igor 🧟');
        }
    }

    protected function doctorSays(string $message, bool $typewriter = true): void
    {
        if ($typewriter) {
            $this->typewriterDialogue('👨‍⚕️ Dr. Frankenstein', $message, 'magenta');
        } else {
            note($message, 'Dr. Frankenstein 👨‍⚕️');
        }
    }

    /**
     * Display dialogue with typewriter effect like a video game
     */
    protected function typewriterDialogue(string $character, string $message, string $color = 'white'): void
    {
        // Display character name
        $this->output->write("\n  <fg={$color};options=bold>{$character}:</>\n  ");

        // Type out message character by character
        $chars = mb_str_split($message);
        $totalChars = count($chars);

        foreach ($chars as $index => $char) {
            $this->output->write("<fg={$color}>{$char}</>");

            // Vary speed based on punctuation for more natural feel
            $delay = match ($char) {
                '.', '!', '?' => 80000,  // Longer pause at sentence end
                ',', ';', ':' => 40000,  // Medium pause at comma
                ' '     => 5000,             // Very short pause at spaces
                default => 20000,        // Normal typing speed (20ms)
            };

            usleep($delay);

            // Add a slight pause every 40 characters for readability
            if (($index + 1) % 40 === 0 && $index < $totalChars - 1) {
                usleep(30000);
            }
        }

        $this->newLine(2);
    }

    /**
     * Pause for dramatic effect
     */
    protected function dramaticPause(int $milliseconds = 500): void
    {
        usleep($milliseconds * 1000);
    }
}
