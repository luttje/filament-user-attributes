<?php

namespace Luttje\FilamentUserAttributes\Commands;

use Illuminate\Console\Command;
use Luttje\FilamentUserAttributes\CodeGeneration\CodeModifier;
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
        $models['0'] = 'No models';
        $chosenModels = $this->choice(
            "Which of your models should be able to have user attributes? (comma separated)",
            $models,
            null,
            null,
            true
        );

        return array_map(
            fn($choice) => $models[$choice],
                array_filter(
                    $chosenModels,
                    fn($choice) => $choice !== '0'
                )
            );
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
            $models[$modelName] = app()->getNamespace() . 'Models\\' . $modelName;
        }

        return $models;
    }

    public static function isModelSetup(string $model): bool
    {
        if (!class_exists($model)) {
            return false;
        }

        return in_array(HasUserAttributes::class, class_uses_recursive($model)) &&
               in_array(HasUserAttributesContract::class, class_implements($model));
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
        $file = $this->getModelFilePath($model);
        $contents = file_get_contents($file);

        $contents = CodeModifier::addTrait($contents, HasUserAttributes::class);
        $contents = CodeModifier::addInterface($contents, HasUserAttributesContract::class);

        file_put_contents($file, $contents);
    }

    private function getModelFilePath(string $model): string
    {
        return app_path(str_replace('\\', '/', substr($model, strlen(app()->getNamespace()))) . '.php');
    }
}
