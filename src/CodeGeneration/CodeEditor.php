<?php

namespace Luttje\FilamentUserAttributes\CodeGeneration;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter;
use PhpParser\NodeVisitor;

/**
 * @internal
 */
final class CodeEditor
{
    protected static $recentBackupPaths = [];

    public static function make()
    {
        return new static();
    }

    public static function getRecentBackupPaths()
    {
        return static::$recentBackupPaths;
    }

    public function editFileWithBackup($path, $callback)
    {
        $transaction = new CodeEditTransaction($path);
        $transaction->edit($callback);

        $backupPath = $transaction->getBackupFilePath();

        if ($backupPath) {
            self::$recentBackupPaths[$path] = $backupPath;
        }

        return $transaction;
    }

    public function addTrait($code, $trait)
    {
        return self::modifyCode(
            $code,
            self::fullyQualifyClass($trait),
            TraitInserter::class
        );
    }

    public function addInterface($code, $interface)
    {
        return self::modifyCode(
            $code,
            self::fullyQualifyClass($interface),
            InterfaceInserter::class
        );
    }

    public function addMethod($code, $methodName, ?\Closure $builder = null)
    {
        return self::modifyCode(
            $code,
            $methodName,
            MethodInserter::class,
            $builder
        );
    }

    public function modifyMethod($code, $methodName, ?\Closure $builder = null)
    {
        return self::modifyCode(
            $code,
            $methodName,
            MethodModifier::class,
            $builder
        );
    }

    private static function traverseUntil($ast, $callback)
    {
        $traverser = new NodeTraverser();
        $visitor = new NodeVisitor\FirstFindingVisitor($callback);

        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getFoundNode();
    }

    private static function parseAndTraverse($code, $callback)
    {
        [$parser, $lexer] = self::makeParserWithLexer();

        try {
            $ast = $parser->parse($code);
        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return false;
        }

        return self::traverseUntil($ast, $callback);
    }

    public static function usesTrait($code, $trait)
    {
        $trait = self::fullyQualifyClass($trait);

        return self::parseAndTraverse($code, function ($node) use ($trait) {
            if ($node instanceof \PhpParser\Node\Stmt\TraitUse) {
                foreach ($node->traits as $traitNode) {
                    if ($traitNode->toCodeString() === $trait) {
                        return true;
                    }
                }
            }

            return false;
        }) !== null;
    }

    public static function implementsInterface($code, $interface)
    {
        $interface = self::fullyQualifyClass($interface);

        return self::parseAndTraverse($code, function ($node) use ($interface) {
            if ($node instanceof \PhpParser\Node\Stmt\Class_) {
                foreach ($node->implements as $interfaceNode) {
                    if ($interfaceNode->toCodeString() === $interface) {
                        return true;
                    }
                }
            }

            return false;
        }) !== null;
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

    /**
     * Traverse the statements and find the variable on which the given method is called.
     */
    public static function findCall($stmts, $variableName, $methodName)
    {
        return self::traverseUntil($stmts, function ($node) use ($variableName, $methodName) {
            if ($node instanceof \PhpParser\Node\Expr\MethodCall) {
                if ($node->var instanceof \PhpParser\Node\Expr\Variable) {
                    if ($node->var->name === $variableName) {
                        if ($node->name->name === $methodName) {
                            return true;
                        }
                    }
                }
            }

            return false;
        });
    }
}
