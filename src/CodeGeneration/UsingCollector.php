<?php

namespace Luttje\FilamentUserAttributes\CodeGeneration;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeVisitor\FirstFindingVisitor;

/**
 * @internal
 */
class UsingCollector extends FirstFindingVisitor
{
    use UsingCollectorTrait;

    private $symbolToFilter;

    public function __construct(string $symbolFilter)
    {
        parent::__construct(function (Node $node) {
            return $this->isNodeFound($node);
        });

        $this->symbolToFilter = new Node\Name\FullyQualified($symbolFilter);
    }

    private function isNameEqualToFilter(Name $node)
    {
        return $node->toCodeString() === $this->symbolToFilter->toString()
            || $this->foundInUsings($node->toCodeString());
    }

    private function isNodeFound(Node $node): bool
    {
        if ($node instanceof Node\Stmt\TraitUse) {
            foreach ($node->traits as $trait) {
                if ($this->isNameEqualToFilter($trait)) {
                    return true;
                }
            }
        } else if ($node instanceof Node\Name) {
            if ($this->isNameEqualToFilter($node)) {
                return true;
            }
        }

        return false;
    }

    public function enterNode(Node $node)
    {
        $this->tryCollectUsings($node, $this->symbolToFilter);

        return parent::enterNode($node);
    }
}
