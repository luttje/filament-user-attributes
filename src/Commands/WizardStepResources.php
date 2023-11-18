<?php

namespace Luttje\FilamentUserAttributes\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Luttje\FilamentUserAttributes\CodeGeneration\CodeEditor;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Traits\UserAttributesResource;

class WizardStepResources extends Command
{
    public function __construct()
    {
        $this->signature = 'filament-user-attributes:wizard-resources';
        $this->description = 'Wizard to help setup your resources with Filament User Attributes';

        parent::__construct();
    }

    public function handle()
    {
        if (!$this->promptForResourcesSetup()) {
            return;
        }

        $this->finalizeResourcesSetup();
    }

    protected function promptForResourcesSetup(): bool
    {
        $models = collect(WizardStepModels::scanForModels())
            ->filter(fn ($model) => in_array(ConfiguresUserAttributesContract::class, class_implements($model)));

        if ($models->isEmpty()) {
            $this->info('(Failing) No models found to have been setup to configure user attributes.');
            return false;
        }

        return $this->confirm('Do you want to setup any resources to display and edit user attributes?', true);
    }

    protected function getModelsImplementingConfiguresUserAttributesContract(): Collection
    {
        return collect(WizardStepModels::scanForModels())
            ->filter(fn ($model) => in_array(ConfiguresUserAttributesContract::class, class_implements($model)));
    }

    protected static function scanForResources(): array
    {
        return collect(glob(app_path('Filament/Resources/*')))
            ->mapWithKeys(function ($file) {
                $resource = app()->getNamespace() . 'Filament\\Resources\\' . basename($file, '.php');
                return [class_basename($resource) => $resource];
            })->toArray();
    }

    protected function getChosenResources(array $resources): array
    {
        return $this->choice(
            'Which resources should display and edit user attributes?',
            array_keys($resources),
            null,
            null,
            true
        );
    }

    protected function finalizeResourcesSetup()
    {
        $resources = self::scanForResources();
        $chosenResources = $this->getChosenResources($resources);

        if (empty($chosenResources)) {
            return;
        }

        $this->setupResources(array_map(fn ($resource) => $resources[$resource], $chosenResources));

        return;
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

        $editor = CodeEditor::make();
        $editor->editFileWithBackup($file, function ($code) use ($editor, $resource) {
            $code = $editor->addTrait($code, UserAttributesResource::class);
            $code = $editor->addInterface($code, UserAttributesConfigContract::class);
            $code = $editor->addMethod($code, 'getUserAttributesConfig', function () use ($resource) {
                $method = new \PhpParser\Node\Stmt\ClassMethod('getUserAttributesConfig', [
                    'flags' => \PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC | \PhpParser\Node\Stmt\Class_::MODIFIER_STATIC,
                    'returnType' => new \PhpParser\Node\NullableType(
                        new \PhpParser\Node\Name\FullyQualified(ConfiguresUserAttributesContract::class)
                    ),
                ]);
                $method->stmts = $this->guessTemplate($resource, $this->getModelsImplementingConfiguresUserAttributesContract());
                return $method;
            });
            $code = self::applyWrapperMethod($editor, $code, 'form', 'schema', 'withUserAttributeFields');
            $code = self::applyWrapperMethod($editor, $code, 'table', 'columns', 'withUserAttributeColumns');
            return $code;
        });
    }

    private static function applyWrapperMethod($editor, $contents, $parentMethodName, $methodNameToWrapInside, $methodNameToCall)
    {
        return $editor->modifyMethod(
            $contents,
            $parentMethodName,
            function ($method) use ($editor, $methodNameToWrapInside, $methodNameToCall) {
                /** @var \PhpParser\Node\Stmt\ClassMethod */
                $method = $method;
                $firstParameter = $method->params[0];
                $schema = $editor->findCall(
                    $method->stmts,
                    $firstParameter->var->name,
                    $methodNameToWrapInside
                );

                if (
                    $schema->args[0]->value instanceof \PhpParser\Node\Expr\StaticCall
                    && $schema->args[0]->value->name->name === $methodNameToCall
                ) {
                    return $method;
                }

                $schema->args = [
                    new \PhpParser\Node\Arg(
                        new \PhpParser\Node\Expr\StaticCall(
                            new \PhpParser\Node\Name('self'),
                            $methodNameToCall,
                            $schema->args
                        )
                    ),
                ];

                return $method;
            }
        );
    }

    protected function guessTemplate(string $resource, Collection $models)
    {
        $this->warn("\nWe will prepare the getUserAttributesConfig method for you, but you'll have to finish it for $resource.");

        $model = $models->first();

        if (!$model) {
            return $this->guessTemplateNoModels($resource);
        }

        if (is_subclass_of($model, \Illuminate\Foundation\Auth\User::class)) {
            return $this->guessTemplateAuthUser();
        }

        if (strstr($model, 'Tenant') !== false) {
            return $this->guessTemplateTenant();
        }

        return $this->guessTemplateNoKnownModels($resource, $models);
    }

    protected function guessTemplateAuthUser()
    {
        $this->line("\nGuessing that your configuration model may be a user model...");

        return [
            new \PhpParser\Node\Stmt\Expression(
                new \PhpParser\Node\Expr\Assign(
                    new \PhpParser\Node\Expr\Variable('user'),
                    new \PhpParser\Node\Expr\MethodCall(
                        new \PhpParser\Node\Expr\StaticCall(
                            new \PhpParser\Node\Name\FullyQualified(\Illuminate\Support\Facades\Auth::class),
                            'user'
                        ),
                        'get'
                    )
                ),
                [
                    'comments' => [
                        new \PhpParser\Comment\Doc(
                            <<<PHPDOC
/** @var \App\Models\User */
PHPDOC
                        ),
                    ],
                ]
            ),
            new \PhpParser\Node\Stmt\Return_(
                new \PhpParser\Node\Expr\Variable('user')
            ),
        ];
    }

    protected function guessTemplateTenant()
    {
        $this->line("\nGuessing that your configuration model may be a multi-tenancy model...");

        return [
            new \PhpParser\Node\Stmt\Expression(
                new \PhpParser\Node\Expr\Assign(
                    new \PhpParser\Node\Expr\Variable('tenant'),
                    new \PhpParser\Node\Expr\StaticCall(
                        new \PhpParser\Node\Name\FullyQualified(\Filament\Facades\Filament::class),
                        'getTenant'
                    ),
                ),
                [
                    'comments' => [
                        new \PhpParser\Comment\Doc(
                            <<<PHPDOC
// TODO: Double-check that this is the correct configuration model for your app.
PHPDOC
                        ),
                    ],
                ]
            ),
            new \PhpParser\Node\Stmt\Return_(
                new \PhpParser\Node\Expr\Variable('tenant')
            ),
        ];
    }

    protected function guessTemplateNoModels(string $resource)
    {
        $this->warn("\nWe didn't find any models that implement the correct interface, so you'll have to do this yourself.");

        return [
            new \PhpParser\Node\Stmt\Throw_(
                new \PhpParser\Node\Expr\New_(
                    new \PhpParser\Node\Name\FullyQualified(\Exception::class),
                    [
                        new \PhpParser\Node\Arg(
                            new \PhpParser\Node\Scalar\String_('You have to implement the getUserAttributesConfig method in ' . $resource . '.')
                        ),
                    ]
                ),
                [
                    'comments' => [
                        new \PhpParser\Comment\Doc(
                            <<<PHPDOC
// TODO: You should finish this method and return the model that configures the user attributes.
// TODO: We didn't find any models that implement the correct interface, so you'll have to do this yourself.
PHPDOC
                        ),
                    ],
                ]
            ),
        ];
    }

    protected function guessTemplateNoKnownModels(string $resource, Collection $models)
    {
        $this->warn("\nWe didn't recognize any of the models that implement the correct interface, so you'll have to finish this implementation yourself.");

        return [
            new \PhpParser\Node\Stmt\Throw_(
                new \PhpParser\Node\Expr\New_(
                    new \PhpParser\Node\Name\FullyQualified(\Exception::class),
                    [
                        new \PhpParser\Node\Arg(
                            new \PhpParser\Node\Scalar\String_('You have to implement the getUserAttributesConfig method in ' . $resource . '.')
                        ),
                    ]
                ),
                [
                    'comments' => [
                        new \PhpParser\Comment\Doc(
                            '// TODO: You should finish this method and return the model that configures the user attributes.' .
                            $models->map(fn ($model) => '\\' . $model . '::class')
                                ->values()
                                ->reduce(fn ($carry, $model) => $carry . ($carry ? "\n" : '') . "\t\t// $model", "\t\t// These are the models that implement the correct interface:")
                        ),
                    ],
                ]
            ),
        ];
    }
}
