<?php
declare(strict_types=1);

namespace App\TreeWalker;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Utils\NodeSourceApi;
use RZ\Roadiz\Core\Bags\NodeTypes;
use RZ\TreeWalker\WalkerContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Stopwatch\Stopwatch;

final class NodeSourceWalkerContext implements WalkerContextInterface
{
    private Stopwatch $stopwatch;
    private NodeTypes $nodeTypesBag;
    private NodeSourceApi $nodeSourceApi;
    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;

    /**
     * @param Stopwatch                     $stopwatch
     * @param NodeTypes                     $nodeTypesBag
     * @param NodeSourceApi                 $nodeSourceApi
     * @param RequestStack                  $requestStack
     * @param EntityManagerInterface        $entityManager
     */
    public function __construct(
        Stopwatch $stopwatch,
        NodeTypes $nodeTypesBag,
        NodeSourceApi $nodeSourceApi,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager
    ) {
        $this->stopwatch = $stopwatch;
        $this->nodeTypesBag = $nodeTypesBag;
        $this->nodeSourceApi = $nodeSourceApi;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    /**
     * @return Stopwatch
     */
    public function getStopwatch(): Stopwatch
    {
        return $this->stopwatch;
    }

    /**
     * @return NodeTypes
     */
    public function getNodeTypesBag(): NodeTypes
    {
        return $this->nodeTypesBag;
    }

    /**
     * @return NodeSourceApi
     */
    public function getNodeSourceApi(): NodeSourceApi
    {
        return $this->nodeSourceApi;
    }

    /**
     * @return RequestStack
     */
    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

    /**
     * @return Request|null
     */
    public function getMasterRequest(): ?Request
    {
        return $this->requestStack->getMasterRequest();
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}
