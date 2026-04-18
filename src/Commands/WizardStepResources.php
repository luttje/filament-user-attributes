<?php

namespace Luttje\FilamentUserAttributes\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Luttje\FilamentUserAttributes\CodeGeneration\CodeEditor;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
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
        $models = $this->getModelsImplementingConfiguresUserAttributesContract();

        if ($models->isEmpty()) {
            $this->info('(Failing) No models found to have been setup to configure user attributes.');
            return false;
        }

        return $this->confirm('Do you want to setup any resources to display and edit user attributes?', true);
    }

    protected function getModelsImplementingConfiguresUserAttributesContract(): Collection
    {
        return collect(FilamentUserAttributes::getConfigurableModels(configuredOnly: false))
            ->filter(fn ($model) => in_array(ConfiguresUserAttributesContract::class, class_implements($model)));
    }

    protected function getChosenResources(array $resources): array
    {
        return $this->choice(
            'Which resources should display and edit user attributes?',
            $resources,
            null,
            null,
            true
        );
    }

    protected function finalizeResourcesSetup()
    {
        $resources = FilamentUserAttributes::getConfigurableResources(configuredOnly: false);

        if (empty($resources)) {
            $this->warn('(Failing) No resources found to setup for user attributes.');
            return;
        }

        $resources = array_keys($resources);
        $chosenResources = $this->getChosenResources($resources);

        if (empty($chosenResources)) {
            return;
        }

        $this->setupResources($chosenResources);

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
        $file = FilamentUserAttributes::findResourceFilePath($resource);
        $originalCode = file_get_contents($file);

        // Detect Filament 5 delegation patterns before modifying the resource
        $formDelegation = self::detectDelegation($originalCode, 'form');
        $tableDelegation = self::detectDelegation($originalCode, 'table');

        $editor = CodeEditor::make();
        $editor->editFileWithBackup($file, function ($code) use ($editor, $resource, $formDelegation, $tableDelegation) {
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

            // Only apply inline wrapping if the method doesn't delegate to another class
            if ($formDelegation === null) {
                $code = self::applyWrapperMethod($editor, $code, 'form', 'schema', 'withUserAttributeFields');
            }
            if ($tableDelegation === null) {
                $code = self::applyWrapperMethod($editor, $code, 'table', 'columns', 'withUserAttributeColumns');
            }

            return $code;
        });

        // Handle Filament 5 delegated form/table classes
        if ($formDelegation !== null) {
            $this->applyWrapperToDelegatedClass($editor, $formDelegation, $resource, 'components', 'withUserAttributeFields');
        }
        if ($tableDelegation !== null) {
            $this->applyWrapperToDelegatedClass($editor, $tableDelegation, $resource, 'columns', 'withUserAttributeColumns');
        }
    }

    /**
     * Detects if a method delegates to another class via SomeClass::configure($param),
     * as is common in Filament 5 resources.
     *
     * @return string|null The FQCN of the delegated class, or null if no delegation detected.
     */
    private static function detectDelegation(string $code, string $methodName): ?string
    {
        $parser = (new \PhpParser\ParserFactory())->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $nodeFinder = new \PhpParser\NodeFinder();

        // Collect use statements for name resolution
        $useMap = [];
        $namespace = '';

        $namespaceNode = $nodeFinder->findFirstInstanceOf($ast, \PhpParser\Node\Stmt\Namespace_::class);
        if ($namespaceNode) {
            $namespace = $namespaceNode->name->toString();

            foreach ($namespaceNode->stmts as $stmt) {
                if ($stmt instanceof \PhpParser\Node\Stmt\Use_) {
                    foreach ($stmt->uses as $use) {
                        $alias = $use->alias ? $use->alias->name : $use->name->getLast();
                        $useMap[$alias] = $use->name->toString();
                    }
                }
            }
        }

        // Find the method
        $method = $nodeFinder->findFirst($ast, function ($node) use ($methodName) {
            return $node instanceof \PhpParser\Node\Stmt\ClassMethod
                && $node->name->name === $methodName;
        });

        if ($method === null || empty($method->stmts)) {
            return null;
        }

        // Look for a return statement with a static call to ::configure()
        $returnStmt = $nodeFinder->findFirst($method->stmts, function ($node) {
            return $node instanceof \PhpParser\Node\Stmt\Return_
                && $node->expr instanceof \PhpParser\Node\Expr\StaticCall
                && $node->expr->name instanceof \PhpParser\Node\Identifier
                && $node->expr->name->name === 'configure';
        });

        if ($returnStmt === null) {
            return null;
        }

        $staticCall = $returnStmt->expr;

        if (!($staticCall->class instanceof \PhpParser\Node\Name)) {
            return null;
        }

        $className = $staticCall->class->toString();

        // Resolve using use statements
        $parts = explode('\\', $className);
        $firstPart = $parts[0];

        if (isset($useMap[$firstPart])) {
            if (count($parts) > 1) {
                array_shift($parts);
                return $useMap[$firstPart] . '\\' . implode('\\', $parts);
            }
            return $useMap[$firstPart];
        }

        // If not in use statements, assume same namespace
        if ($namespace) {
            return $namespace . '\\' . $className;
        }

        return $className;
    }

    /**
     * Applies the wrapper method in a delegated class file (Filament 5 pattern).
     */
    private function applyWrapperToDelegatedClass(
        CodeEditor $editor,
        string $delegatedClass,
        string $resourceClass,
        string $methodNameToWrapInside,
        string $methodNameToCall
    ): void {
        if (!class_exists($delegatedClass)) {
            $this->warn("Could not find delegated class $delegatedClass");
            return;
        }

        $refClass = new \ReflectionClass($delegatedClass);
        $file = $refClass->getFileName();

        if (!$file) {
            $this->warn("Could not determine file path for $delegatedClass");
            return;
        }

        $editor->editFileWithBackup($file, function ($code) use ($editor, $resourceClass, $methodNameToWrapInside, $methodNameToCall) {
            return self::applyWrapperMethod(
                $editor,
                $code,
                'configure',
                $methodNameToWrapInside,
                $methodNameToCall,
                $resourceClass
            );
        });
    }

    private static function applyWrapperMethod($editor, $contents, $parentMethodName, $methodNameToWrapInside, $methodNameToCall, ?string $callerClass = null)
    {
        return $editor->modifyMethod(
            $contents,
            $parentMethodName,
            function ($method) use ($editor, $methodNameToWrapInside, $methodNameToCall, $callerClass) {
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

                $callerName = $callerClass !== null
                    ? new \PhpParser\Node\Name\FullyQualified($callerClass)
                    : new \PhpParser\Node\Name('self');

                $schema->args = [
                    new \PhpParser\Node\Arg(
                        new \PhpParser\Node\Expr\StaticCall(
                            $callerName,
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
                    new \PhpParser\Node\Expr\StaticCall(
                        new \PhpParser\Node\Name\FullyQualified(\Illuminate\Support\Facades\Auth::class),
                        'user'
                    ),
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
