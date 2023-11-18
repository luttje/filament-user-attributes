<?php

namespace Luttje\FilamentUserAttributes\CodeGeneration;

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

    public static function clearRecentBackupPaths()
    {
        static::$recentBackupPaths = [];
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

    private static function traverseUntilUsing($ast, $symbolFilter)
    {
        $traverser = new NodeTraverser();
        $visitor = new UsingCollector($symbolFilter);
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getFoundNode();
    }

    private static function parseAndTraverseUntilUsing($code, $symbolFilter)
    {
        [$parser, $lexer] = self::makeParserWithLexer();
        $ast = $parser->parse($code);

        return self::traverseUntilUsing($ast, $symbolFilter);
    }

    public static function usesTrait($code, $trait)
    {
        $trait = self::fullyQualifyClass($trait);
        $node = self::parseAndTraverseUntilUsing($code, $trait);

        return $node !== null;
    }

    public static function implementsInterface($code, $interface)
    {
        $interface = self::fullyQualifyClass($interface);
        $node = self::parseAndTraverseUntilUsing($code, $interface);

        return $node !== null;
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

        $ast = $parser->parse($code);
        $origTokens = $lexer->getTokens();

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
     * Recrusively traverse the statements and find the variable on this method is called.
     *
     * @param \PhpParser\Node $node A method or variable
     */
    private static function getOriginalVariableName($node)
    {
        if ($node instanceof \PhpParser\Node\Expr\MethodCall) {
            return self::getOriginalVariableName($node->var);
        } elseif ($node instanceof \PhpParser\Node\Expr\Variable) {
            return $node->name;
        }

        return false;
    }

    /**
     * Traverse the statements and find the variable on which the given method is called.
     */
    public static function findCall($stmts, $variableName, $methodName)
    {
        return self::traverseUntil($stmts, function ($node) use ($variableName, $methodName) {
            if (!($node instanceof \PhpParser\Node\Expr\MethodCall)) {
                return false;
            }

            $varName = self::getOriginalVariableName($node->var);

            return $varName === $variableName
                && $node->name->name === $methodName;
        });
    }
}
