<?php
declare(strict_types=1);

namespace App\Serialization;

use Doctrine\Common\Cache\CacheProvider;
use JMS\Serializer\Context;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Exclusion\DisjunctExclusionStrategy;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\TreeWalker\AbstractWalker;
use RZ\TreeWalker\WalkerContextInterface;

final class BlockWalkerSubscriber implements EventSubscriberInterface
{
    /**
     * @var class-string<AbstractWalker>
     */
    private string $walkerClass;
    private WalkerContextInterface $walkerContext;
    private CacheProvider $cacheProvider;
    private int $maxLevel;

    /**
     * @param class-string<AbstractWalker> $walkerClass
     * @param WalkerContextInterface $walkerContext
     * @param CacheProvider $cacheProvider
     * @param int $maxLevel
     */
    public function __construct(
        string $walkerClass,
        WalkerContextInterface $walkerContext,
        CacheProvider $cacheProvider,
        int $maxLevel = 4
    ) {
        $this->walkerClass = $walkerClass;
        $this->walkerContext = $walkerContext;
        $this->cacheProvider = $cacheProvider;
        $this->maxLevel = $maxLevel;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_serialize',
            'method' => 'onPostSerialize',
        ]];
    }

    private function getPropertyMetadata(Context $context): PropertyMetadata
    {
        $groups = array_unique(array_merge($context->getAttribute('groups'), [
            'walker',
            'children'
        ]));
        return new StaticPropertyMetadata(
            'Collection',
            'blocks',
            [],
            $groups
        );
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $nodeSource = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();
        $exclusionStrategy = $event->getContext()->getExclusionStrategy() ?? new DisjunctExclusionStrategy();
        $blocksProperty = $this->getPropertyMetadata($context);

        if (!$exclusionStrategy->shouldSkipProperty($blocksProperty, $context) &&
            $context->hasAttribute('groups') &&
            in_array('nodes_sources', $context->getAttribute('groups')) &&
            !in_array('no_blocks', $context->getAttribute('groups'))) {
            if ($nodeSource instanceof NodesSources &&
                null !== $nodeSource->getNode() &&
                $visitor instanceof SerializationVisitorInterface &&
                $nodeSource->getNode()->isPublished() &&
                null !== $nodeSource->getNode()->getNodeType() &&
                $nodeSource->getNode()->getNodeType()->isReachable()
            ) {
                $blockNodeSourceWalkerClass = $this->walkerClass;
                $blockWalker = $blockNodeSourceWalkerClass::build(
                    $nodeSource,
                    $this->walkerContext,
                    $this->maxLevel,
                    $this->cacheProvider
                );
                $visitor->visitProperty(
                    $blocksProperty,
                    $blockWalker->getChildren()
                );
            }
        }
    }
}
