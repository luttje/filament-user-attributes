<?php

namespace Luttje\FilamentUserAttributes\Commands\Traits;

use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

trait WizardSetsUpModels
{
    protected function stepModels(string $prefix, array &$bag): bool
    {
        $models = self::scanForModels();
        $models['0'] = 'No models';
        $chosenModels = $this->choice(
            "$prefix Which of your models should be able to have user attributes? (comma separated)",
            $models,
            null,
            null,
            true,
        );
        $modelsToSetup = count($chosenModels);

        foreach ($chosenModels as $key => $displayName) {
            if($displayName == '0') {
                $modelsToSetup--;
                continue;
            }

            if ($key === 0) {
                $this->info('The following models will be setup to support user attributes:');
            }

            $model = $models[$displayName];
            $isSetup = self::isModelSetup($model);

            if($isSetup) {
                $this->line("<fg=green>- $model (already setup)</>");
                $modelsToSetup--;
                continue;
            }

            $this->line("- $model");
        }

        if ($modelsToSetup === 0) {
            return true;
        }

        $this->setupModels(array_map(fn ($displayName) => $models[$displayName], $chosenModels));

        return true;
    }

    public static function scanForModels()
    {
        $models = [];

        foreach (glob(app_path('Models/*.php')) as $model) {
            $model = app()->getNamespace() . 'Models\\' . basename($model, '.php');
            $displayName = basename($model, '.php');
            $models[$displayName] = $model;
        }

        return $models;
    }

    public static function isModelSetup(string $model): bool
    {
        if (!class_exists($model)) {
            return false;
        }

        $traits = class_uses_recursive($model);
        if (!in_array(HasUserAttributes::class, $traits)) {
            return false;
        }

        $interfaces = class_implements($model);
        if (!in_array(HasUserAttributesContract::class, $interfaces)) {
            return false;
        }

        return true;
    }

    protected function setupModels(array $models)
    {
        $this->warn("\nWe will now implement the HasUserAttributesContract interface with the HasUserAttributes trait for you...");

        foreach ($models as $model) {
            $this->setupModel($model);
        }
    }

    protected function setupModel(string $model)
    {
        $file = app_path(str_replace('\\', '/', substr($model, strlen(app()->getNamespace()))) . '.php');
        $contents = file_get_contents($file);

        // Insert the trait after the class definition
        $traits = class_uses_recursive($model);

        $className = HasUserAttributes::class;
        if (!in_array($className, $traits)) {
            $classDefinitionEnd = strpos($contents, '{', strpos($contents, 'class '));
            $contents = substr_replace($contents, "\n    use \\$className;", $classDefinitionEnd + 1, 0);
        }

        // Insert the interface after the class definition, checking if there's already an 'implements'
        $interfaces = class_implements($model);

        $className = HasUserAttributesContract::class;
        if (!in_array(HasUserAttributesContract::class, $interfaces)) {
            $classDeclarationEnd = strpos($contents, '{', strpos($contents, 'class ')) - 1;
            $implements = strpos($contents, 'implements') !== false ? ', ' : ' implements ';
            $contents = substr_replace($contents, $implements . "\\$className", $classDeclarationEnd, 0);
        }

        file_put_contents($file, $contents);
    }
}
