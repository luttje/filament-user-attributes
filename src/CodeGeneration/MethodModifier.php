<?php

namespace Luttje\FilamentUserAttributes\CodeGeneration;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class MethodModifier extends NodeVisitorAbstract
{
    private $methodNameToModify;

    private $methodModifier;

    public function __construct(string $methodNameToAdd, ?\Closure $builder = null)
    {
        $this->methodNameToModify = $methodNameToAdd;
        $this->methodModifier = $builder;
    }

    public function enterNode(Node $node)
    {
        if (!($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        $methodKey = null;

        foreach ($node->stmts as $key => $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                if ($stmt->name->toString() === $this->methodNameToModify) {
                    $methodKey = $key;
                    break;
                }
            }
        }

        if ($methodKey !== null) {
            $builder = $this->methodModifier;
            $node->stmts[$methodKey] = $builder($node->stmts[$methodKey]);
        }

        return null;
    }
}
