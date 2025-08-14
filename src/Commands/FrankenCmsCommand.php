<?php

namespace FrankenCms\Commands;

use Illuminate\Console\Command;

class FrankenCmsCommand extends Command
{
    public $signature = 'franken-cms';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
