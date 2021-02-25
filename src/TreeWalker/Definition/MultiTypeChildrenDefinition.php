<?php
declare(strict_types=1);

namespace App\TreeWalker\Definition;

use App\TreeWalker\NodeSourceWalkerContext;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\TreeWalker\Definition\ContextualDefinitionTrait;
use RZ\TreeWalker\WalkerContextInterface;

final class MultiTypeChildrenDefinition
{
    use ContextualDefinitionTrait;

    /**
     * @var array
     */
    private $types;

    /**
     * @param WalkerContextInterface $context
     * @param array<string> $types
     */
    public function __construct(WalkerContextInterface $context, array $types)
    {
        $this->context = $context;
        $this->types = $types;
    }

    /**
     * @param NodesSources $source
     * @return array|Paginator
     */
    public function __invoke(NodesSources $source)
    {
        if ($this->context instanceof NodeSourceWalkerContext) {
            $this->context->getStopwatch()->start(static::class);
            $bag = $this->context->getNodeTypesBag();
            $children = $this->context->getNodeSourceApi()->getBy([
                'node.parent' => $source->getNode(),
                'node.visible' => true,
                'translation' => $source->getTranslation(),
                'node.nodeType' => array_map(function (string $singleType) use ($bag) {
                    return $bag->get($singleType);
                }, $this->types)
            ], [
                'node.position' => 'ASC',
            ]);
            $this->context->getStopwatch()->stop(static::class);

            return $children;
        }
        throw new \InvalidArgumentException('Context should be instance of ' . NodeSourceWalkerContext::class);
    }
}
