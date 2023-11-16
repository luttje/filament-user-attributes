<?php

namespace Luttje\FilamentUserAttributes\Commands;

use Illuminate\Console\Command;

class WizardCommand extends Command
{
    public function __construct()
    {
        $this->signature = 'filament-user-attributes:wizard';
        $this->description = 'Wizard to help setup your project with Filament User Attributes';

        parent::__construct();
    }

    public function handle()
    {
        $this->info('This wizard will help you setup your project with Filament User Attributes.');

        $commands = [
            WizardStepModels::class,
            WizardStepConfig::class,
            WizardStepResources::class,
        ];

        foreach ($commands as $command) {
            $this->call($command);
        }

        $this->info("\nFilament User Attributes has finished setting up your project!");
    }
}
