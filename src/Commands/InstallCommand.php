<?php

namespace FrankenCms\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    public $signature = 'franken-cms:install';

    public $description = 'Install the Franken CMS';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
