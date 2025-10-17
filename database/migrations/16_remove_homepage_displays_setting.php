<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Remove the homepage_displays setting as it's no longer used
        // Only delete if it exists (for existing installations)
        $exists = DB::table('settings')
            ->where('group', 'cms_reading')
            ->where('name', 'homepage_displays')
            ->exists();

        if ($exists) {
            $this->migrator->delete('cms_reading.homepage_displays');
        }
    }

    public function down(): void
    {
        // Restore the setting if rolling back
        $this->migrator->add('cms_reading.homepage_displays', 'latest_posts');
    }
};
