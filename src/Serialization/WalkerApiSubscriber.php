<?php
declare(strict_types=1);

namespace App\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\TreeWalker\WalkerInterface;

final class WalkerApiSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_serialize',
            'method' => 'onPostSerialize',
            'priority' => -1000, // optional priority
        ]];
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $walker = $event->getObject();
        $visitor = $event->getVisitor();

        if ($visitor instanceof SerializationVisitorInterface &&
            $walker instanceof WalkerInterface) {
            $className = get_class($walker);
            $classTokens = explode('\\', $className);
            $visitor->visitProperty(
                new StaticPropertyMetadata('string', '@type', []),
                end($classTokens)
            );
        }
    }
}
