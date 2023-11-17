<?php

namespace Luttje\FilamentUserAttributes\Tests\CodeGeneration;

use Luttje\FilamentUserAttributes\CodeGeneration\CodeEditor;

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
