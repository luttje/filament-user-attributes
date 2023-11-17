<?php

namespace Luttje\FilamentUserAttributes\Commands;

use Illuminate\Console\Command;
use Luttje\FilamentUserAttributes\CodeGeneration\CodeEditor;
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
        return $choice;
    }

    protected function setupConfigModel(string $model): void
    {
        $file = $this->getModelFilePath($model);

        $editor = CodeEditor::make();
        $editor->editFileWithBackup($file, function ($code) use ($editor) {
            $code = $editor->addTrait($code, ConfiguresUserAttributes::class);
            $code = $editor->addInterface($code, ConfiguresUserAttributesContract::class);
            return $code;
        });
    }

    private function getModelFilePath(string $model): string
    {
        return app_path(str_replace('\\', '/', substr($model, strlen(app()->getNamespace()))) . '.php');
    }
}
