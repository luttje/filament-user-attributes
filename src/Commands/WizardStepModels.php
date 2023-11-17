<?php

namespace Luttje\FilamentUserAttributes\Commands;

use Illuminate\Console\Command;
use Luttje\FilamentUserAttributes\CodeGeneration\CodeTraverser;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
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
            "Do add support for user attributes to your models?",
            true
        );
    }

    public function finalizeModelSetup()
    {
        $models = self::scanForModels();
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

    public static function scanForModels(): array
    {
        $models = [];
        $modelFiles = glob(app_path('Models/*.php'));

        foreach ($modelFiles as $file) {
            $modelName = basename($file, '.php');
            $models[] = app()->getNamespace() . 'Models\\' . $modelName;
        }

        return $models;
    }

    public static function isModelSetup(string $model): bool
    {
        $filePath = self::getModelFilePath($model);
        if (!file_exists($filePath)) {
            return false;
        }

        $code = file_get_contents($filePath);
        return CodeTraverser::usesTrait($code, HasUserAttributes::class) &&
               CodeTraverser::implementsInterface($code, HasUserAttributesContract::class);
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
        $file = self::getModelFilePath($model);
        $contents = file_get_contents($file);

        $contents = CodeTraverser::addTrait($contents, HasUserAttributes::class);
        $contents = CodeTraverser::addInterface($contents, HasUserAttributesContract::class);

        file_put_contents($file, $contents);
    }

    private static function getModelFilePath(string $model): string
    {
        return app_path(str_replace('\\', '/', substr($model, strlen(app()->getNamespace()))) . '.php');
    }
}
