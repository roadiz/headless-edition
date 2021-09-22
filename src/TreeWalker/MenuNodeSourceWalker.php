<?php
declare(strict_types=1);

namespace App\TreeWalker;

//use App\TreeWalker\Definition\MultiTypeChildrenDefinition;
use RZ\TreeWalker\AbstractWalker;

/**
 * @package App\TreeWalker
 */
final class MenuNodeSourceWalker extends AbstractWalker
{
    protected function initializeDefinitions(): void
    {
        if ($this->isRoot()) {
            $context = $this->getContext();
            if ($context instanceof NodeSourceWalkerContext) {
                /*$this->addDefinition(
                    NSMenu::class,
                    new MultiTypeChildrenDefinition($context, [
                        'Menu',
                        'Page',
                    ])
                );*/
            }
        }
    }
}
