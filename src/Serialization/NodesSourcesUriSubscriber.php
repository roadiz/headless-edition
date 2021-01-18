<?php
declare(strict_types=1);

namespace App\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NodesSourcesUriSubscriber implements EventSubscriberInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
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

    public function onPostSerialize(ObjectEvent $event): void
    {
        $nodeSource = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if ($context->hasAttribute('groups') &&
            in_array('urls', $context->getAttribute('groups'))) {
            if ($nodeSource instanceof NodesSources &&
                null !== $nodeSource->getNode() &&
                null !== $nodeSource->getNode()->getNodeType() &&
                $visitor instanceof SerializationVisitorInterface &&
                $nodeSource->getNode()->isPublished() &&
                $nodeSource->getNode()->getNodeType()->isReachable()
            ) {
                $visitor->visitProperty(
                    new StaticPropertyMetadata('string', 'url', []),
                    $this->urlGenerator->generate(
                        RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                        [
                            RouteObjectInterface::ROUTE_OBJECT => $nodeSource
                        ],
                        UrlGeneratorInterface::ABSOLUTE_PATH
                    )
                );
            }
        }
    }
}
