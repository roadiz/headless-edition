<?php
declare(strict_types=1);

namespace App\Serialization;

use Doctrine\Common\Cache\CacheProvider;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\TreeWalker\AbstractWalker;
use RZ\TreeWalker\WalkerContextInterface;
use Themes\AbstractApiTheme\Serialization\AbstractReachableNodesSourcesPostSerializationSubscriber;

final class BlockWalkerSubscriber extends AbstractReachableNodesSourcesPostSerializationSubscriber
{
    /**
     * @var class-string<AbstractWalker>
     */
    private string $walkerClass;
    private WalkerContextInterface $walkerContext;
    private CacheProvider $cacheProvider;
    private int $maxLevel;

    protected function atRoot(): bool
    {
        return true;
    }

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
        $this->propertyMetadata = new StaticPropertyMetadata(
            'Collection',
            'blocks',
            [],
            ['children'] // This is groups to allow this property to be serialized
        );
        $this->propertyMetadata->skipWhenEmpty = true;
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $nodeSource = $event->getObject();
        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();
        /** @var SerializationContext $context */
        $context = $event->getContext();

        if ($this->supports($event, $this->propertyMetadata)) {
            if ($context->hasAttribute('locks')) {
                $context->getAttribute('locks')->add(static::class);
            }
            $blockNodeSourceWalkerClass = $this->walkerClass;
            $blockWalker = $blockNodeSourceWalkerClass::build(
                $nodeSource,
                $this->walkerContext,
                $this->maxLevel,
                $this->cacheProvider
            );
            $visitor->visitProperty(
                $this->propertyMetadata,
                $blockWalker->getChildren()
            );
        }
    }
}
