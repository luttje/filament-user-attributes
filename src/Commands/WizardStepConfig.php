<?php

namespace Luttje\FilamentUserAttributes\Commands;

use Illuminate\Console\Command;
use Luttje\FilamentUserAttributes\CodeGeneration\CodeTraverser;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes;

class WizardStepConfig extends Command
{
    public function __construct()
    {
        $this->signature = 'filament-user-attributes:wizard-config';
        $this->description = 'Wizard to help setup your config model with Filament User Attributes';

        parent::__construct();
    }

    public function handle()
    {
        if (!$this->promptForConfigSetup()) {
            return;
        }

        $this->finalizeConfigSetup();
    }

    protected function promptForConfigSetup(): bool
    {
        $this->listConfigurableModels();

        return $this->confirm(
            "Do you want to let a model (like user or tenant) configure user attributes?",
            true
        );
    }

    protected function finalizeConfigSetup(): void
    {
        $model = $this->selectModelForConfig("Which model should configure user attributes?");
        $this->setupConfigModel($model);
    }

    private function listConfigurableModels(): void
    {
        $this->line('The following models are setup to support user attributes:');

        collect(WizardStepModels::scanForModels())
            ->filter(fn ($model) => WizardStepModels::isModelSetup($model))
            ->each(fn ($model) => $this->line("- $model"));
    }

    private function selectModelForConfig(string $prompt): string
    {
        $models = WizardStepModels::scanForModels();
        $choice = $this->choice($prompt, $models);
        return $models[$choice];
    }

    protected function setupConfigModel(string $model): void
    {
        $file = $this->getModelFilePath($model);
        $contents = file_get_contents($file);

        $contents = CodeTraverser::addTrait($contents, ConfiguresUserAttributes::class);
        $contents = CodeTraverser::addInterface($contents, ConfiguresUserAttributesContract::class);

        file_put_contents($file, $contents);
    }

    private function getModelFilePath(string $model): string
    {
        return app_path(str_replace('\\', '/', substr($model, strlen(app()->getNamespace()))) . '.php');
    }
}
