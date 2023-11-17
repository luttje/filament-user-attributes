<?php

namespace Luttje\FilamentUserAttributes\CodeGeneration;

use PhpParser\Node;

/**
 * @internal
 */
trait UsingCollectorTrait
{
    protected $collectedUsing = null;

    /**
     * Collects usings to match against later
     */
    protected function tryCollectUsings(Node $node, Node\Name\FullyQualified $fullName)
    {
        if ($node instanceof Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                if ($use->name->toCodeString() !== ltrim($fullName->toString(), '\\')) {
                    return;
                }

                $this->collectedUsing = $use->getAlias()->name;
            }
        }
    }

    protected function foundInUsings($classOrAlias)
    {
        if ($this->collectedUsing === null) {
            return false;
        }

        return $this->collectedUsing === $classOrAlias;
    }
}
