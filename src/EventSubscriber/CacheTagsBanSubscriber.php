<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Events\Node\NodeUpdatedEvent;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Themes\AbstractApiTheme\Cache\CacheTagsCollection;

final class CacheTagsBanSubscriber implements EventSubscriberInterface
{
    private array $configuration;
    private bool $debug;
    private LoggerInterface $logger;
    private CacheTagsCollection $cacheTagsCollection;

    /**
     * @param array $configuration
     * @param bool $debug
     * @param LoggerInterface $logger
     * @param CacheTagsCollection $cacheTagsCollection
     */
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

        $this->banCacheTag($event->getNode());
    }

    public function onNodesSourcesUpdated(NodesSourcesUpdatedEvent $event): void
    {
        if (!$this->supportConfig() ||
            $event->getNodeSource()->getNode() === null) {
            return;
        }

        $this->banCacheTag($event->getNodeSource()->getNode());
    }

    private function createBanRequests(Node $node): array
    {
        $cacheTag = $this->cacheTagsCollection->getCacheTagForNode($node);
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

    private function banCacheTag(Node $node): void
    {
        try {
            foreach ($this->createBanRequests($node) as $name => $request) {
                (new Client())->send($request, [
                    'debug' => $this->debug
                ]);
                $this->logger->debug(sprintf(
                    'Reverse proxy cache-tag for node "%s" banned.',
                    $node->getNodeName()
                ));
            }
        } catch (GuzzleException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
