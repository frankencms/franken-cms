<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Remove the old twitter_card_type setting
        $this->migrator->delete('franken-cms-seo.twitter_card_type');

        // Add the new use_twitter_summary_card boolean
        // Default to false (use large image cards with OG image)
        // Users who were using summary cards will need to re-enable this
        $this->migrator->add('franken-cms-seo.use_twitter_summary_card', false);
    }

    public function down(): void
    {
        // Remove the new setting
        $this->migrator->delete('franken-cms-seo.use_twitter_summary_card');

        // Re-add the old setting with default value
        $this->migrator->add('franken-cms-seo.twitter_card_type', 'summary_large_image');
    }
};
