<?php

namespace Luttje\FilamentUserAttributes\CodeGeneration;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class TraitModifier extends NodeVisitorAbstract
{
    private $traitToAdd;

    private $traitBuilder;

    public function __construct(string $traitToAdd, ?\Closure $builder = null)
    {
        $this->traitToAdd = new Node\Name\FullyQualified($traitToAdd);
        $this->traitBuilder = $builder;
    }

    public function enterNode(Node $node)
    {
        if (!($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        $traitUses = $node->getTraitUses();

        $found = false;
        foreach ($traitUses as $traitUse) {
            foreach ($traitUse->traits as $trait) {
                if ($trait->toCodeString() === $this->traitToAdd->toString()) {
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            // Add the trait to the beginning of the class
            $builder = $this->traitBuilder;
            array_unshift($node->stmts, new Node\Stmt\Nop()); // Whitespace after the trait
            array_unshift($node->stmts, $builder ? $builder() : new Node\Stmt\TraitUse([new Node\Name($this->traitToAdd)]));
        }

        return null;
    }
}
