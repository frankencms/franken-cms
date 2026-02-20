<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Robots.txt Settings Group (franken_cms_robots)
        $this->migrator->add('franken_cms_robots.enabled', true);

        // Discourage indexing (blocks all search engines)
        $this->migrator->add('franken_cms_robots.discourage_indexing', false);

        // Default: Allow all bots to crawl everything
        $this->migrator->add('franken_cms_robots.user_agents', [
            [
                'user_agent'  => '*',
                'rules'       => ['Allow: /'],
                'crawl_delay' => null,
            ],
        ]);
    }
};
