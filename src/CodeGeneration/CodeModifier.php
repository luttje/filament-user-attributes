<?php

namespace Luttje\FilamentUserAttributes\CodeGeneration;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter;
use PhpParser\NodeVisitor;

class CodeModifier
{
    public static function addTrait($code, $trait)
    {
        return self::modifyCode(
            $code,
            self::fullyQualifyClass($trait),
            TraitModifier::class
        );
    }

    public static function addInterface($code, $interface)
    {
        return self::modifyCode(
            $code,
            self::fullyQualifyClass($interface),
            InterfaceModifier::class
        );
    }

    public static function addMethod($code, $methodName, ?\Closure $builder = null)
    {
        return self::modifyCode(
            $code,
            $methodName,
            MethodModifier::class,
            $builder
        );
    }

    public static function fullyQualifyClass($class)
    {
        if (strpos($class, '\\') === 0) {
            return $class;
        }

        return '\\' . $class;
    }

    private static function makeParserWithLexer()
    {
        $lexer = new \PhpParser\Lexer\Emulative([
            'usedAttributes' => [
                'comments',
                'startLine', 'endLine',
                'startTokenPos', 'endTokenPos',
            ],
        ]);
        $parser = new \PhpParser\Parser\Php7($lexer);

        return [$parser, $lexer];
    }

    public static function astFromTemplate($template)
    {
        [$parser, $lexer] = self::makeParserWithLexer();
        return $parser->parse($template);
    }

    private static function modifyCode($code, $nodeName, $modifierClass, ?\Closure $builder = null)
    {
        [$parser, $lexer] = self::makeParserWithLexer();

        // Create a traverser to clone the AST so we can keep the original formatting
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NodeVisitor\CloningVisitor());

        try {
            $ast = $parser->parse($code);
            $origTokens = $lexer->getTokens();
        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return $code;
        }

        $modifiedAst = $traverser->traverse($ast);

        // Create a new traverser to modify the AST
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new $modifierClass($nodeName, $builder));

        $modifiedAst = $traverser->traverse($modifiedAst);

        // Preserve most formatting
        $prettyPrinter = new PrettyPrinter\Standard();
        return $prettyPrinter->printFormatPreserving($modifiedAst, $ast, $origTokens);
    }
}
