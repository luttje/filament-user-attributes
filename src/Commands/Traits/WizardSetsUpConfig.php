<?php

namespace Luttje\FilamentUserAttributes\Commands\Traits;

use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes;

trait WizardSetsUpConfig
{
    protected function getConfigSteps(): array
    {
        return [
            fn ($prefix, &$bag) => $this->stepConfig($prefix, $bag),
            fn ($prefix, &$bag) => $this->stepConfigCommit($prefix, $bag),
        ];
    }

    protected function stepConfig(string $prefix, array &$bag): bool
    {
        $this->line('The following models are setup to support user attributes:');

        collect(self::scanForModels())
            ->filter(fn ($model) => WizardSetsUpModels::isModelSetup($model))
            ->each(fn ($model) => $this->line("- $model"));

        $shouldSetupConfig = $this->confirm("$prefix Do you want to let a model (like user or tenant) configure user attributes for the above models?", true);

        if (!$shouldSetupConfig) {
            $bag['wantsConfigModel'] = false;
            return true;
        }

        return true;
    }

    protected function stepConfigCommit(string $prefix, array &$bag): bool
    {
        if (isset($bag['wantsConfigModel'])
            && !$bag['wantsConfigModel']) {
            $this->line("$prefix (Skipping) No (additional) model will be setup to configure user attributes.");
            return true;
        }

        $models = self::scanForModels();
        $configModel = $this->choice(
            "$prefix Which model should be able to configure user attributes?",
            $models,
        );

        $this->setupConfigModel($models[$configModel]);

        return true;
    }

    protected function setupConfigModel(string $model)
    {
        $file = app_path(str_replace('\\', '/', substr($model, strlen(app()->getNamespace()))) . '.php');
        $contents = file_get_contents($file);

        // Insert the trait after the class definition
        $traits = class_uses_recursive($model);

        $className = ConfiguresUserAttributes::class;
        if (!in_array($className, $traits)) {
            $classDefinitionEnd = strpos($contents, '{', strpos($contents, 'class '));

            $contents = substr_replace($contents, "\n    use \\$className;", $classDefinitionEnd + 1, 0);
        }

        // Insert the interface after the class definition, checking if there's already an 'implements'
        $interfaces = class_implements($model);

        $className = ConfiguresUserAttributesContract::class;
        if (!in_array($className, $interfaces)) {
            $classDeclarationEnd = strpos($contents, '{', strpos($contents, 'class ')) - 1;
            $implements = strpos($contents, 'implements') !== false ? ', ' : ' implements ';
            $contents = substr_replace($contents, $implements . "\\$className", $classDeclarationEnd, 0);
        }

        file_put_contents($file, $contents);
    }
}
