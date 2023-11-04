<?php

namespace Luttje\FilamentUserAttributes\Commands;

use Illuminate\Console\Command;

class FilamentUserAttributesCommand extends Command
{
    public $signature = 'filament-user-attributes';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
