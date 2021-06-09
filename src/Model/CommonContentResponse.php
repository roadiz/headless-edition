<?php
declare(strict_types=1);

namespace App\Model;

use App\TreeWalker\MenuNodeSourceWalker;
use Doctrine\Common\Cache\CacheProvider;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\CMS\Utils\NodeSourceApi;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\TreeWalker\WalkerContextInterface;
use RZ\TreeWalker\WalkerInterface;

final class CommonContentResponse
{
    /*
     * Add here any model you want to serialize and expose
     * for your common content API entry point
     */
    /**
     * @Serializer\Exclude
     */
    private ?WalkerInterface $mainMenuWalker = null;

    /**
     * @var WalkerContextInterface
     * @Serializer\Exclude
     */
    private WalkerContextInterface $walkerContext;
    /**
     * @Serializer\Exclude
     */
    private CacheProvider $cacheProvider;
    /**
     * @Serializer\Exclude
     */
    private NodeSourceApi $nodeSourceApi;
    /**
     * @var TranslationInterface
     * @Serializer\Exclude
     */
    private TranslationInterface $translation;
    /**
     * @Serializer\Groups({"walker"})
     */
    private NodesSourcesHead $head;

    /**
     * @param NodeSourceApi $nodeSourceApi
     * @param TranslationInterface $translation
     * @param WalkerContextInterface $walkerContext
     * @param CacheProvider $cacheProvider
     * @param NodesSourcesHead $head
     */
    public function __construct(
        NodeSourceApi $nodeSourceApi,
        TranslationInterface $translation,
        WalkerContextInterface $walkerContext,
        CacheProvider $cacheProvider,
        NodesSourcesHead $head
    ) {
        $this->walkerContext = $walkerContext;
        $this->cacheProvider = $cacheProvider;
        $this->translation = $translation;
        $this->nodeSourceApi = $nodeSourceApi;
        $this->head = $head;
    }

    /**
     * @return WalkerInterface
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"walker"})
     */
    public function getMainMenuWalker(): WalkerInterface
    {
        if (null === $this->mainMenuWalker) {
            $mainMenu = $this->nodeSourceApi->getOneBy([
                'node.nodeName' => 'main-menu',
                'translation' => $this->translation
            ]);
            $this->mainMenuWalker = MenuNodeSourceWalker::build(
                $mainMenu,
                $this->walkerContext,
                3,
                $this->cacheProvider
            );
        }
        return $this->mainMenuWalker;
    }

    /**
     * @return int Time-to-live in **seconds**
     */
    public function getTtl(): int
    {
        return 10*60;
    }

    /**
     * @return NodesSourcesHead
     */
    public function getHead(): NodesSourcesHead
    {
        return $this->head;
    }
}
