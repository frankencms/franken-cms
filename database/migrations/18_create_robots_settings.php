<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Robots.txt Settings Group (franken-cms-robots)
        $this->migrator->add('franken-cms-robots.enabled', true);

        // Default: Allow all bots to crawl everything
        $this->migrator->add('franken-cms-robots.user_agents', [
            [
                'user_agent' => '*',
                'rules' => ['Allow: /'],
                'crawl_delay' => null,
            ],
        ]);

        $this->migrator->add('franken-cms-robots.additional_sitemaps', []);
        $this->migrator->add('franken-cms-robots.host', null);
    }
};
