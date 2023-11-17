<?php

namespace Luttje\FilamentUserAttributes\Commands;

use Illuminate\Console\Command;
use Luttje\FilamentUserAttributes\CodeGeneration\CodeEditor;

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

        $recentBackups = CodeEditor::getRecentBackupPaths();

        if (count($recentBackups) > 0) {
            $this->info('<fg=gray>The following files were modified by Filament User Attributes. We have created a back-up of each file.</>');

            foreach ($recentBackups as $file => $backup) {
                $this->info("<fg=gray>$file (back-up $backup)</>");
            }
        } else {
            $this->info('<fg=gray>None of your files were modified by Filament User Attributes.</>');
        }

        $this->info("\nFilament User Attributes has finished setting up your project!");
    }
}
