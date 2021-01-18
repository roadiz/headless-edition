<?php
declare(strict_types=1);

namespace App\TreeWalker;

use RZ\TreeWalker\AbstractWalker;
use RZ\TreeWalker\Definition\ZeroChildrenDefinition;
use App\TreeWalker\Definition\NonReachableNodeSourceBlockDefinition;

final class BlockNodeSourceWalker extends AbstractWalker
{
    protected function initializeDefinitions(): void
    {
        $zeroChildren = new ZeroChildrenDefinition($this->getContext());
        $nonReachableNodeSourceDefinition = new NonReachableNodeSourceBlockDefinition($this->getContext());

        /*
         * Add your own business logic here
         */
        // $this->addDefinition(NSPage::class, $nonReachableNodeSourceDefinition);
        // $this->addDefinition(NSNeutral::class, $zeroChildren);
    }
}
