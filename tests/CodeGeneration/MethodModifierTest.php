<?php

namespace Luttje\FilamentUserAttributes\Tests\CodeGeneration;

use Luttje\FilamentUserAttributes\CodeGeneration\CodeEditor;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;

it('can add a method to a class', function () {
    $contents = file_get_contents(__DIR__ . '/../../src/Commands/WizardCommand.php');

    // Create a temporary file to edit
    $file = tempnam(sys_get_temp_dir(), 'filament-user-attributes');
    file_put_contents($file, $contents);

    $editor = CodeEditor::make();
    $edit = $editor->editFileWithBackup($file, function ($code) use ($editor) {
        return $editor->addMethod($code, 'getUserAttributesConfig', function () {
            $method = new \PhpParser\Node\Stmt\ClassMethod('getUserAttributesConfig', [
                'flags' => \PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC | \PhpParser\Node\Stmt\Class_::MODIFIER_STATIC,
                'returnType' => new \PhpParser\Node\NullableType(
                    new \PhpParser\Node\Name\FullyQualified(ConfiguresUserAttributesContract::class)
                ),
            ]);
            $method->stmts = [
                new \PhpParser\Node\Stmt\Return_(
                    new \PhpParser\Node\Expr\New_(
                        new \PhpParser\Node\Name\FullyQualified(Config::class)
                    )
                ),
            ];
            return $method;
        });
    });

    expect($edit->getCode())->toContain('public static function getUserAttributesConfig() : ?\Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract');
});
