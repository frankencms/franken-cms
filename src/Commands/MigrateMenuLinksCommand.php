<?php

declare(strict_types=1);

namespace FrankenCms\Commands;

use FrankenCms\Models\MenuItem;
use FrankenCms\Models\Page;
use FrankenCms\Models\Post;
use FrankenCms\Services\MenuService;
use Illuminate\Console\Command;

class MigrateMenuLinksCommand extends Command
{
    protected $signature = 'franken-cms:migrate-menu-links';

    protected $description = 'Migrate menu items with stored URLs to use linkable relationships';

    public function handle(): int
    {
        $this->info('Migrating menu links to use linkable relationships...');

        $menuItems = MenuItem::whereNull('linkable_type')
            ->whereNotNull('url')
            ->get();

        if ($menuItems->isEmpty()) {
            $this->info('No menu items need migration.');

            return self::SUCCESS;
        }

        $migrated = 0;
        $skipped = 0;

        foreach ($menuItems as $menuItem) {
            $url = $menuItem->url;

            // Skip external URLs
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '#')) {
                $skipped++;

                continue;
            }

            // Try to find a matching page by hierarchical path
            $page = Page::all()->first(function ($page) use ($url) {
                return $page->getHierarchicalPath() === $url;
            });

            if ($page) {
                $menuItem->linkable_type = Page::class;
                $menuItem->linkable_id = $page->id;
                $menuItem->save();
                $migrated++;
                $this->line("  Migrated '{$menuItem->label}' -> Page: {$page->post_title}");

                continue;
            }

            // Try to find a matching post
            $post = Post::where('post_type', 'post')
                ->where('post_slug', ltrim($url, '/'))
                ->first();

            if ($post) {
                $menuItem->linkable_type = Post::class;
                $menuItem->linkable_id = $post->id;
                $menuItem->save();
                $migrated++;
                $this->line("  Migrated '{$menuItem->label}' -> Post: {$post->post_title}");

                continue;
            }

            $skipped++;
            $this->warn("  Could not find matching page/post for: {$menuItem->label} ({$url})");
        }

        $this->newLine();
        $this->info("Migration complete: {$migrated} migrated, {$skipped} skipped");

        // Clear all menu caches
        $this->info('Clearing menu caches...');
        app(MenuService::class)->clearAllMenuCaches();

        return self::SUCCESS;
    }
}
