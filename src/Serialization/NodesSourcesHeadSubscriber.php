<?php
declare(strict_types=1);

namespace App\Serialization;

use App\Model\NodesSourcesHead;
use App\Model\NodesSourcesHeadFactory;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Themes\AbstractApiTheme\Serialization\AbstractReachableNodesSourcesPostSerializationSubscriber;

final class NodesSourcesHeadSubscriber extends AbstractReachableNodesSourcesPostSerializationSubscriber
{
    private NodesSourcesHeadFactory $nodesSourcesHeadFactory;

    protected function once(): bool
    {
        return true;
    }

    protected function atRoot(): bool
    {
        return true;
    }

    protected static function getPriority(): int
    {
        return 1000;
    }

    /**
     * @param NodesSourcesHeadFactory $nodesSourcesHeadFactory
     */
    public function __construct(NodesSourcesHeadFactory $nodesSourcesHeadFactory)
    {
        $this->nodesSourcesHeadFactory = $nodesSourcesHeadFactory;
        $this->propertyMetadata = new StaticPropertyMetadata(
            NodesSourcesHead::class,
            'head',
            [],
            [
                'nodes_sources_single',
            ]
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
            $head = $this->nodesSourcesHeadFactory->createForNodeSource($nodeSource);
            $visitor->visitProperty(
                $this->propertyMetadata,
                $head
            );
        }
    }
}
