<?php

namespace Luttje\FilamentUserAttributes\Commands;

use Illuminate\Console\Command;
use Luttje\FilamentUserAttributes\CodeGeneration\CodeEditor;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
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

        // Disable auto loading the models by setting configured to false
        collect(FilamentUserAttributes::getConfigurableModels(configured: false))
            ->filter(fn ($model) => WizardStepModels::isModelSetup($model))
            ->each(fn ($model) => $this->line("- $model"));
    }

    private function selectModelForConfig(string $prompt): string
    {
        $models = FilamentUserAttributes::getConfigurableModels(configured: false);
        $choice = $this->choice($prompt, $models);
        return $choice;
    }

    protected function setupConfigModel(string $model): void
    {
        $file = FilamentUserAttributes::findModelFilePath($model);

        $editor = CodeEditor::make();
        $editor->editFileWithBackup($file, function ($code) use ($editor) {
            $code = $editor->addTrait($code, ConfiguresUserAttributes::class);
            $code = $editor->addInterface($code, ConfiguresUserAttributesContract::class);
            return $code;
        });
    }
}
