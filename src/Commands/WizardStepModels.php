<?php

namespace Luttje\FilamentUserAttributes\Commands;

use Illuminate\Console\Command;
use Luttje\FilamentUserAttributes\CodeGeneration\CodeEditor;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

class WizardStepModels extends Command
{
    public function __construct()
    {
        $this->signature = 'filament-user-attributes:wizard-models';
        $this->description = 'Wizard to help setup your models with Filament User Attributes';

        parent::__construct();
    }

    public function handle()
    {
        if (!$this->promptForModelSetup()) {
            return;
        }

        $this->finalizeModelSetup();
    }

    protected function promptForModelSetup(): bool
    {
        return $this->confirm(
            "Do you want to add support for user attributes to your models?",
            true
        );
    }

    public function finalizeModelSetup()
    {
        $models = FilamentUserAttributes::getConfigurableModels(configuredOnly: false);
        $chosenModels = $this->getChosenModels($models);

        $this->displaySelectedModels($chosenModels);

        if (empty($chosenModels)) {
            return;
        }

        $this->setupModels($chosenModels);

        return;
    }

    protected function getChosenModels(array $models): array
    {
        $choices = array_values($models);

        $chosenModels = $this->choice(
            "Which of your models should be able to have user attributes? (comma separated)",
            $choices,
            null,
            null,
            true
        );

        return $chosenModels;
    }

    protected function displaySelectedModels(array $chosenModels): void
    {
        if (empty($chosenModels)) {
            return;
        }

        $this->info('The following models will be setup to support user attributes:');
        foreach ($chosenModels as $model) {
            $setupStatus = self::isModelSetup($model) ? "<fg=green>(already setup)</>" : "";
            $this->line("- $model $setupStatus");
        }
    }

    public static function isModelSetup(string $model): bool
    {
        $file = FilamentUserAttributes::findModelFilePath($model);
        if (!file_exists($file)) {
            return false;
        }

        $code = file_get_contents($file);
        return CodeEditor::usesTrait($code, HasUserAttributes::class) &&
               CodeEditor::implementsInterface($code, HasUserAttributesContract::class);
    }

    protected function setupModels(array $models): void
    {
        $this->warn("\nWe will now implement the HasUserAttributesContract interface with the HasUserAttributes trait for you...");

        foreach ($models as $model) {
            $this->setupModel($model);
        }
    }

    protected function setupModel(string $model): void
    {
        $file = FilamentUserAttributes::findModelFilePath($model);

        $editor = CodeEditor::make();
        $editor->editFileWithBackup($file, function ($code) use ($editor) {
            $code = $editor->addTrait($code, HasUserAttributes::class);
            $code = $editor->addInterface($code, HasUserAttributesContract::class);
            return $code;
        });
    }
}
