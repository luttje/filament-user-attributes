<?php

namespace Luttje\FilamentUserAttributes\Commands\Traits;

use Illuminate\Support\Collection;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Traits\UserAttributesResource;

trait WizardSetsUpResources
{
    protected function getResourcesSteps(): array
    {
        return [
            fn ($prefix, $bag) => $this->stepResources($prefix, $bag),
            fn ($prefix, $bag) => $this->stepResourcesCommit($prefix, $bag),
        ];
    }

    protected function stepResources(string $prefix, array &$bag): bool
    {
        $models = collect(WizardSetsUpModels::scanForModels())
            ->filter(fn ($model) => class_implements($model, UserAttributesConfigContract::class));

        if ($models->isEmpty()) {
            $this->info("$prefix (Failing) No models found to have been setup to configure user attributes.");
            return false;
        }

        $shouldSetupResources = $this->confirm("$prefix Do you want to setup any resources to display and edit user attributes?", true);

        if (!$shouldSetupResources) {
            $bag['wantsResources'] = false;
            return true;
        }

        return true;
    }

    protected static function scanForResources(): array
    {
        $resources = [];

        foreach (glob(app_path('Filament/Resources/*')) as $file) {
            $resource = app()->getNamespace() . 'Filament\\Resources\\' . basename($file, '.php');
            $displayName = basename($resource, '.php');
            $resources[$displayName] = $resource;
        }

        return $resources;
    }

    protected function stepResourcesCommit(string $prefix, array &$bag): bool
    {
        if (isset($bag['wantsResources'])
            && !$bag['wantsResources']) {
            return true;
        }

        $resources = self::scanForResources();
        $chosenResources = $this->choice(
            "$prefix Which resources should display and edit user attributes?",
            $resources,
            null,
            null,
            true
        );

        $resourcesToSetup = count($chosenResources);

        foreach ($chosenResources as $key => $displayName) {
            if($displayName == '0') {
                $resourcesToSetup--;
                continue;
            }

            if ($key === 0) {
                $this->info('The following models will be setup to support user attributes:');
            }

            $resource = $resources[$displayName];
            $isSetup = self::isResourceSetup($resource);

            if($isSetup) {
                $this->line("<fg=green>- $resource (already setup)</>");
                $resourcesToSetup--;
                continue;
            }

            $this->line("- $resource");
        }

        if ($resourcesToSetup === 0) {
            return true;
        }

        $this->setupResources(array_map(fn ($resource) => $resources[$resource], $chosenResources));

        return true;
    }

    public static function isResourceSetup(string $resource): bool
    {
        if (!class_exists($resource)) {
            return false;
        }

        $traits = class_uses_recursive($resource);
        if (!in_array(UserAttributesResource::class, $traits)) {
            return false;
        }

        $interfaces = class_implements($resource);
        if (!in_array(UserAttributesConfigContract::class, $interfaces)) {
            return false;
        }

        if (!method_exists($resource, 'getUserAttributesConfig')
        || !(new \ReflectionMethod($resource, 'getUserAttributesConfig'))->isPublic()) {
            return false;
        }

        return true;
    }

    protected function setupResources(array $resources)
    {
        foreach ($resources as $resource) {
            $this->setupResource($resource);
        }
    }

    protected function setupResource(string $resource)
    {
        $file = app_path(str_replace('\\', '/', substr($resource, strlen(app()->getNamespace()))) . '.php');
        $contents = file_get_contents($file);

        // Insert the trait after the class definition
        $traits = class_uses_recursive($resource);

        $className = UserAttributesResource::class;
        if (!in_array($className, $traits)) {
            $classDefinitionEnd = strpos($contents, '{', strpos($contents, 'class '));

            $contents = substr_replace($contents, "\n    use \\$className;\n", $classDefinitionEnd + 1, 0);
        }

        // Insert the interface after the class definition, checking if there's already an 'implements'
        $interfaces = class_implements($resource);

        $className = UserAttributesConfigContract::class;
        if (!in_array($className, $interfaces)) {
            $classDeclarationEnd = strpos($contents, '{', strpos($contents, 'class ')) - 1;
            $implements = strpos($contents, 'implements') !== false ? ', ' : ' implements ';
            $contents = substr_replace($contents, $implements . "\\$className", $classDeclarationEnd, 0);
        }

        if (!method_exists($resource, 'getUserAttributesConfig')
        || !(new \ReflectionMethod($resource, 'getUserAttributesConfig'))->isPublic()) {
            // Insert the method to return the model that configures the user attributes
            $models = collect(WizardSetsUpModels::scanForModels())
                ->filter(fn ($resource) => in_array(ConfiguresUserAttributesContract::class, class_implements($resource)));

            $classConfigurer = '\\'.ConfiguresUserAttributesContract::class;
            $templateContents = $this->guessTemplate($resource, $models);
            $template = "\n\n".<<<TEMPLATE
    public static function getUserAttributesConfig(): ?$classConfigurer
    {
$templateContents
    }
TEMPLATE;

            $lastClosingBracePosition = strrpos($contents, '}');
            $positionBeforeClosingBrace = $lastClosingBracePosition - strlen('}');
            $contents = substr_replace($contents, $template, $positionBeforeClosingBrace, 0);

            file_put_contents($file, $contents);
        }
    }

    protected function guessTemplate(string $resource, Collection $models)
    {
        $this->warn("\nWe will prepare the getUserAttributesConfig method for you, but you'll have to finish it for $resource.");

        $model = $models->first();

        if (!$model) {
            return $this->guessTemplateNoModels($resource);
        }

        if (is_subclass_of($model, \Illuminate\Foundation\Auth\User::class)) {
            return $this->guessTemplateAuthUser($resource, $model);
        }

        if (strstr($model, 'Tenant') !== false) {
            return $this->guessTemplateTenant($resource, $model);
        }

        return $this->guessTemplateNoKnownModels($resource, $models);
    }

    protected function guessTemplateAuthUser(string $resource, string $model)
    {
        $this->warn("\nGuessing that your configurer model may be the Auth user model...");

        return <<<TEMPLATE
        /** @var \App\Models\User */
        // TODO: Double-check that this is the correct configuration model for your app.
        \$user = \Illuminate\Support\Facades\Auth::user();
        return \$user;
TEMPLATE;
    }

    protected function guessTemplateTenant(string $resource, string $model)
    {
        $this->warn("\nGuessing that your configurer model may be the Tenant model...");

        return <<<TEMPLATE
        // TODO: Double-check that this is the correct configuration model for your app.
        \$tenant = \Filament\Facades\Filament::getTenant();
        return \$tenant;
TEMPLATE;
    }

    protected function guessTemplateNoModels(string $resource)
    {
        return <<<TEMPLATE
        // TODO: You should finish this method and return the model that configures the user attributes.
        // TODO: We didn't find any models that implement the correct interface, so you'll have to do this yourself.
        throw new \Exception('You have to implement the getUserAttributesConfig method in $resource.');
TEMPLATE;
    }

    protected function guessTemplateNoKnownModels(string $resource, Collection $models)
    {
        $modelComments = $models->map(fn ($model) => '\\' . $model . '::class')
            ->values()
            ->reduce(fn ($carry, $model) => $carry . ($carry ? "\n" : '') . "\t\t// $model", "\t\t// These are the models that implement the correct interface:");

        return <<<TEMPLATE
        // TODO: You should finish this method and return the model that configures the user attributes.
$modelComments
        throw new \Exception('You have to implement the getUserAttributesConfig method in $resource.');
TEMPLATE;
    }
}
