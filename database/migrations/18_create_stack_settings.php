<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cms_stacks.stacks', []);
    }

    public function down(): void
    {
        $this->migrator->delete('cms_stacks.stacks');
    }
};
