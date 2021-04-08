<?php
declare(strict_types=1);

namespace App\Model;

use RZ\Roadiz\CMS\Utils\NodeSourceApi;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NodesSourcesHeadFactory
{
    private Settings $settingsBag;
    private UrlGeneratorInterface $urlGenerator;
    private NodeSourceApi $nodeSourceApi;

    /**
     * @param Settings $settingsBag
     * @param UrlGeneratorInterface $urlGenerator
     * @param NodeSourceApi $nodeSourceApi
     */
    public function __construct(
        Settings $settingsBag,
        UrlGeneratorInterface $urlGenerator,
        NodeSourceApi $nodeSourceApi
    ) {
        $this->settingsBag = $settingsBag;
        $this->urlGenerator = $urlGenerator;
        $this->nodeSourceApi = $nodeSourceApi;
    }

    public function createForNodeSource(NodesSources $nodesSources): NodesSourcesHead
    {
        return new NodesSourcesHead($nodesSources, $this->settingsBag, $this->urlGenerator, $this->nodeSourceApi);
    }
}
