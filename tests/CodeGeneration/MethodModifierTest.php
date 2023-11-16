<?php

namespace Luttje\FilamentUserAttributes\Tests\CodeGeneration;

use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;

it('can add a method to a class', function () {
    $code = file_get_contents(__DIR__ . '/../../src/Commands/WizardCommand.php');

    $code = \Luttje\FilamentUserAttributes\CodeGeneration\CodeModifier::addMethod($code, 'testMethod', function () {
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

    expect($code)->toContain('public static function getUserAttributesConfig() : ?\Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract');
});
