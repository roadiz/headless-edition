<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Events\DocumentTranslationUpdatedEvent;
use RZ\Roadiz\Core\Events\DocumentUpdatedEvent;
use RZ\Roadiz\Core\Events\Node\NodeUpdatedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesUpdatedEvent;
use RZ\Roadiz\Core\Events\Tag\TagUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Themes\AbstractApiTheme\Cache\CacheTagsCollection;

final class CacheTagsBanSubscriber implements EventSubscriberInterface
{
    private array $configuration;
    private bool $debug;
    private LoggerInterface $logger;
    private CacheTagsCollection $cacheTagsCollection;

    public function __construct(
        array $configuration,
        CacheTagsCollection $cacheTagsCollection,
        LoggerInterface $logger,
        bool $debug = false
    ) {
        $this->configuration = $configuration;
        $this->debug = $debug;
        $this->logger = $logger;
        $this->cacheTagsCollection = $cacheTagsCollection;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            NodeUpdatedEvent::class => ['onNodeUpdated'],
            NodesSourcesUpdatedEvent::class => ['onNodesSourcesUpdated'],
            TagUpdatedEvent::class => ['onTagUpdated'],
            DocumentUpdatedEvent::class => ['onDocumentUpdated'],
            DocumentTranslationUpdatedEvent::class => ['onDocumentTranslationUpdated'],
        ];
    }

    /**
     * @return bool
     */
    protected function supportConfig(): bool
    {
        return isset($this->configuration['reverseProxyCache']) &&
            count($this->configuration['reverseProxyCache']['frontend']) > 0;
    }

    public function onNodeUpdated(NodeUpdatedEvent $event): void
    {
        if (!$this->supportConfig()) {
            return;
        }

        $this->banCacheTag($event->getNode(), $event->getNode()->getNodeName());
    }

    public function onTagUpdated(TagUpdatedEvent $event): void
    {
        if (!$this->supportConfig()) {
            return;
        }

        $this->banCacheTag($event->getTag(), $event->getTag()->getTagName());
    }

    public function onDocumentUpdated(DocumentUpdatedEvent $event): void
    {
        if (!$this->supportConfig()) {
            return;
        }
        $document = $event->getDocument();
        if ($document instanceof Document) {
            $this->banCacheTag($document, (string) $document);
        }
    }

    public function onDocumentTranslationUpdated(DocumentTranslationUpdatedEvent $event): void
    {
        if (!$this->supportConfig()) {
            return;
        }
        $document = $event->getDocument();
        if ($document instanceof Document) {
            $this->banCacheTag($document, (string) $document);
        }
    }

    public function onNodesSourcesUpdated(NodesSourcesUpdatedEvent $event): void
    {
        if (!$this->supportConfig() ||
            $event->getNodeSource()->getNode() === null) {
            return;
        }

        $this->banCacheTag(
            $event->getNodeSource()->getNode(),
            $event->getNodeSource()->getNode()->getNodeName()
        );
    }

    private function createBanRequests(AbstractEntity $entity): array
    {
        if ($entity instanceof Node) {
            $cacheTag = $this->cacheTagsCollection->getCacheTagForNode($entity);
        } elseif ($entity instanceof Document) {
            $cacheTag = $this->cacheTagsCollection->getCacheTagForDocument($entity);
        } elseif ($entity instanceof Tag) {
            $cacheTag = $this->cacheTagsCollection->getCacheTagForTag($entity);
        } else {
            throw new \InvalidArgumentException(
                'Cache-tag invalidation only supports Node, Document and Tag entities.'
            );
        }
        $requests = [];
        foreach ($this->configuration['reverseProxyCache']['frontend'] as $name => $frontend) {
            $requests[$name] = new Request(
                'BAN',
                'http://' . $frontend['host'],
                [
                    'Host' => $frontend['domainName'],
                    'X-Cache-Tags' => $cacheTag
                ]
            );
        }
        return $requests;
    }

    private function banCacheTag(AbstractEntity $entity, string $identifier): void
    {
        try {
            foreach ($this->createBanRequests($entity) as $name => $request) {
                (new Client())->send($request, [
                    'debug' => $this->debug
                ]);
                $this->logger->debug(sprintf(
                    'Reverse proxy cache-tag for entity "%s" banned.',
                    $identifier
                ));
            }
        } catch (GuzzleException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
