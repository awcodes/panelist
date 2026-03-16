<?php

declare(strict_types=1);

namespace Awcodes\Panelist\Commands;

use Illuminate\Console\Command;

class PanelistCommand extends Command
{
    public $signature = 'panelist';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
