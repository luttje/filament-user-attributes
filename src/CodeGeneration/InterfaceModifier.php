<?php

namespace Luttje\FilamentUserAttributes\CodeGeneration;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class InterfaceModifier extends NodeVisitorAbstract
{
    private $interfaceToAdd;

    private $interfaceBuilder;

    public function __construct(string $interfaceToAdd, array $builder = null)
    {
        $this->interfaceToAdd = new Node\Name\FullyQualified($interfaceToAdd);
        $this->interfaceBuilder = $builder;
    }

    public function enterNode(Node $node)
    {
        if (!($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        $found = false;

        foreach ($node->implements as $implement) {
            if ($implement->toCodeString() === $this->interfaceToAdd->toString()) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $builder = $this->interfaceBuilder;
            $node->implements[] = $builder ? $builder() : new Node\Name($this->interfaceToAdd);
        }

        return null;
    }
}
