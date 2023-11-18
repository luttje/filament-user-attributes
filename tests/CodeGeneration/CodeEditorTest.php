<?php

namespace Luttje\FilamentUserAttributes\Tests\CodeGeneration;

use Luttje\FilamentUserAttributes\CodeGeneration\CodeEditor;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

it('adds a trait to a class that does not already use it', function () {
    $code = '<?php class TestClass {}';
    $traitName = HasUserAttributes::class;

    $editor = CodeEditor::make();
    $modifiedCode = $editor->addTrait($code, HasUserAttributes::class);

    expect($modifiedCode)->toContain("use \\$traitName;");
});

it('does not duplicate an existing trait', function () {
    $traitName = 'TestTrait';
    $code = "<?php class TestClass { use \\$traitName; }";

    $editor = CodeEditor::make();
    $modifiedCode = $editor->addTrait($code, $traitName);

    // Count occurrences to ensure it's only there once
    $count = substr_count($modifiedCode, "use \\$traitName;");
    expect($count)->toEqual(1);
});

it('adds an interface to a class that does not implement it', function () {
    $code = '<?php class TestClass {}';
    $interfaceName = 'TestInterface';

    $editor = CodeEditor::make();
    $modifiedCode = $editor->addInterface($code, $interfaceName);

    expect($modifiedCode)->toContain("implements \\$interfaceName");
});

it('does not duplicate an existing interface', function () {
    $interfaceName = 'TestInterface';
    $code = "<?php class TestClass implements \\$interfaceName {}";

    $editor = CodeEditor::make();
    $modifiedCode = $editor->addInterface($code, $interfaceName);

    $count = substr_count($modifiedCode, "\\$interfaceName");
    expect($count)->toEqual(1);
});

it('correctly prefixes class names with a backslash if not already present', function () {
    $className = 'TestClass';
    $fullyQualified = CodeEditor::fullyQualifyClass($className);

    expect($fullyQualified)->toEqual('\\' . $className);
});

it('can detect if a class uses a trait and implements an interface', function () {
    $code = <<<PHP
<?php

namespace Luttje\FilamentUserAttributes\Tests\CodeGeneration;

use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

class TestClass implements HasUserAttributesContract
{
    use HasUserAttributes;

    public function handle()
    {
        return 'original';
    }
}
PHP;

    expect(CodeEditor::usesTrait($code, HasUserAttributes::class))->toBeTrue();
    expect(CodeEditor::implementsInterface($code, HasUserAttributesContract::class))->toBeTrue();
});

it('can detect if a class uses a trait and implements an interface with aliasing', function () {
    $code = <<<PHP
<?php

namespace Luttje\FilamentUserAttributes\Tests\CodeGeneration;

use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract as MyContract;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributes as HasUserAttributesTrait;

class TestClass implements MyContract
{
    use HasUserAttributesTrait;

    public function handle()
    {
        return 'original';
    }
}
PHP;

    expect(CodeEditor::usesTrait($code, HasUserAttributes::class))->toBeTrue();
    expect(CodeEditor::implementsInterface($code, HasUserAttributesContract::class))->toBeTrue();
});

it('can detect if a class uses a trait and implements an interface when fully qualified', function () {
    $code = <<<PHP
<?php

namespace Luttje\FilamentUserAttributes\Tests\CodeGeneration;

class TestClass implements \Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract
{
    use \Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

    public function handle()
    {
        return 'original';
    }
}
PHP;

    expect(CodeEditor::usesTrait($code, HasUserAttributes::class))->toBeTrue();
    expect(CodeEditor::implementsInterface($code, HasUserAttributesContract::class))->toBeTrue();
});

$codeWithoutTraitOrInterface = <<<PHP
<?php

namespace Luttje\FilamentUserAttributes\Tests\CodeGeneration;

class TestClass
{
    public function handle()
    {
        return 'original';
    }
}
PHP;

it('can detect if a class does not use a trait and does not implement an interface', function () use ($codeWithoutTraitOrInterface) {
    expect(CodeEditor::usesTrait($codeWithoutTraitOrInterface, HasUserAttributes::class))->toBeFalse();
    expect(CodeEditor::implementsInterface($codeWithoutTraitOrInterface, HasUserAttributesContract::class))->toBeFalse();
});

it('can detect if a class does not use a trait and does not implement an interface with possibly mismatched aliasing', function () use ($codeWithoutTraitOrInterface) {
    $code = <<<PHP
<?php

namespace Luttje\FilamentUserAttributes\Tests\CodeGeneration;

use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContractWrong as HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributesWrong as HasUserAttributes;

class TestClass implements MyContract
{
    use HasUserAttributesTrait;

    public function handle()
    {
        return 'original';
    }
}
PHP;

    expect(CodeEditor::usesTrait($code, HasUserAttributes::class))->toBeFalse();
    expect(CodeEditor::implementsInterface($code, HasUserAttributesContract::class))->toBeFalse();
});

//findCall($stmts, $variableName, $methodName)
$codeWithDeepNestedCalls = <<<PHP
<?php

namespace Luttje\FilamentUserAttributes\Tests\CodeGeneration;

class TestClass
{
    public function handle()
    {
        return \$this->getSomething()->getSomethingElse()->getSomethingElseAgain();
    }

    public function getSomething()
    {
        return \$this
            ->getSomethingElse()
            ->getSomethingElseAgain()
            ->getSomethingElseAgainAgain(
                function (\$someVar) {
                    return \$someVar->getSomethingElseAgainAgainAgain();
                }
            );
    }
}
PHP;

it('can find a call in a deeply nested method chain', function () use ($codeWithDeepNestedCalls) {
    $editor = CodeEditor::make();

    $found = false;

    $editor->modifyMethod(
        $codeWithDeepNestedCalls,
        'getSomething',
        function ($method) use ($editor, &$found) {
            /** @var \PhpParser\Node\Stmt\ClassMethod */
            $method = $method;
            $call = $editor->findCall($method->stmts, 'someVar', 'getSomethingElseAgainAgainAgain');

            if ($call) {
                $found = true;
            }

            return $method;
        }
    );

    expect($found)->toBeTrue();
});

it('can find a call in a deeply nested method chain without false positive', function () use ($codeWithDeepNestedCalls) {
    $editor = CodeEditor::make();

    $foundNotExisting = false;

    $editor->modifyMethod(
        $codeWithDeepNestedCalls,
        'getSomething',
        function ($method) use ($editor, &$foundNotExisting) {
            /** @var \PhpParser\Node\Stmt\ClassMethod */
            $method = $method;
            $call = $editor->findCall($method->stmts, 'someVar', 'thisAintAnywhere');

            if ($call) {
                $foundNotExisting = true;
            }

            return $method;
        }
    );

    expect($foundNotExisting)->toBeFalse();
});

it('can find a call in a deeply nested method chain on $this instance after a few method calls', function () use ($codeWithDeepNestedCalls) {
    $editor = CodeEditor::make();

    $foundThis = false;

    $editor->modifyMethod(
        $codeWithDeepNestedCalls,
        'getSomething',
        function ($method) use ($editor, &$foundThis) {
            /** @var \PhpParser\Node\Stmt\ClassMethod */
            $method = $method;
            $call = $editor->findCall($method->stmts, 'this', 'getSomethingElseAgainAgain');

            if ($call) {
                $foundThis = true;
            }

            return $method;
        }
    );

    expect($foundThis)->toBeTrue();
});
