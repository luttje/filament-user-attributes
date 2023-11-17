<?php

namespace Luttje\FilamentUserAttributes\CodeGeneration;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 */
class MethodInserter extends NodeVisitorAbstract
{
    private $methodNameToAdd;

    private $methodBuilder;

    public function __construct(string $methodNameToAdd, ?\Closure $builder = null)
    {
        $this->methodNameToAdd = $methodNameToAdd;
        $this->methodBuilder = $builder;
    }

    public function enterNode(Node $node)
    {
        if (!($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        $found = false;

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                if ($stmt->name->toString() === $this->methodNameToAdd) {
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            $builder = $this->methodBuilder;
            $node->stmts[] = new Node\Stmt\Nop();
            $node->stmts[] = $builder();
        }

        return null;
    }
}
