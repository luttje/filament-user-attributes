<?php

namespace Luttje\FilamentUserAttributes\Commands;

use Illuminate\Console\Command;
use Luttje\FilamentUserAttributes\Commands\Traits\WizardSetsUpConfig;
use Luttje\FilamentUserAttributes\Commands\Traits\WizardSetsUpModels;
use Luttje\FilamentUserAttributes\Commands\Traits\WizardSetsUpResources;

class WizardCommand extends Command
{
    use WizardSetsUpModels;
    use WizardSetsUpConfig;
    use WizardSetsUpResources;

    public function __construct()
    {
        $this->signature = 'filament-user-attributes:wizard';

        $this->description = 'Wizard to help setup your project with Filament User Attributes';

        parent::__construct();
    }

    public function handle()
    {
        $this->info('This wizard will help you setup your project with Filament User Attributes.');

        $steps = [
            fn ($prefix, &$bag) => $this->stepModels($prefix, $bag),
            ...$this->getConfigSteps(),
            ...$this->getResourcesSteps(),
        ];

        $bag = [];
        $totalSteps = count($steps);
        foreach ($steps as $i => $step) {
            $iHuman = $i + 1;
            $success = $step("[Step $iHuman/$totalSteps]", $bag);

            if (!$success) {
                $this->error('The wizard has been aborted.');
                return;
            }
        }

        $this->info("\nFilament User Attributes has finished setting up your project!");
    }
}
