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
use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
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
        /** @var array<string> $groups */
        $groups = $context->hasAttribute('groups') ? $context->getAttribute('groups') : [];
        $groups = array_unique(array_merge($groups, [
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

    private function supportsNodeType(?NodeTypeInterface $nodeType): bool
    {
        /*
         * Customize here where block property is allowed. NodeType is for root entity.
         */
        return $nodeType !== null;
    }

    private function supports(ObjectEvent $event, PropertyMetadata $propertyMetadata): bool
    {
        $nodeSource = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();
        $exclusionStrategy = $context->getExclusionStrategy() ?? new DisjunctExclusionStrategy();
        /** @var array<string> $groups */
        $groups = $context->hasAttribute('groups') ? $context->getAttribute('groups') : [];
        /** @var NodeTypeInterface|null $nodeType */
        $nodeType = $context->hasAttribute('nodeType') ? $context->getAttribute('nodeType') : null;

        return !$exclusionStrategy->shouldSkipProperty($propertyMetadata, $context) &&
            in_array('nodes_sources', $groups) &&
            !in_array('no_blocks', $groups) &&
            $this->supportsNodeType($nodeType) &&
            $nodeSource instanceof NodesSources &&
            null !== $nodeSource->getNode() &&
            $visitor instanceof SerializationVisitorInterface &&
            $nodeSource->getNode()->isPublished() &&
            null !== $nodeSource->getNode()->getNodeType() &&
            $nodeSource->getNode()->getNodeType()->isReachable();
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $nodeSource = $event->getObject();
        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();
        $context = $event->getContext();
        $blocksProperty = $this->getPropertyMetadata($context);

        if ($this->supports($event, $blocksProperty)) {
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
