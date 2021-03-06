<?php
declare(strict_types=1);

namespace App\TreeWalker\Definition;

use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\TreeWalker\Definition\ContextualDefinitionTrait;
use App\TreeWalker\NodeSourceWalkerContext;

final class ReachableNodeSourceDefinition
{
    use ContextualDefinitionTrait;

    public function __invoke(NodesSources $source): array
    {
        if ($this->context instanceof NodeSourceWalkerContext) {
            $this->context->getStopwatch()->start(static::class);
            $children = $this->context->getNodeSourceApi()->getBy([
                'node.parent' => $source->getNode(),
                'node.visible' => true,
                'translation' => $source->getTranslation(),
                'node.nodeType.reachable' => true
            ], [
                'node.position' => 'ASC',
            ]);
            $this->context->getStopwatch()->stop(static::class);

            if ($children instanceof Paginator) {
                return $children->getIterator()->getArrayCopy();
            }
            return $children;
        }
        throw new \InvalidArgumentException('Context should be instance of ' . NodeSourceWalkerContext::class);
    }
}
