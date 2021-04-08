<?php
declare(strict_types=1);

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\CMS\Utils\NodeSourceApi;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NodesSourcesHead
{
    /**
     * @var NodesSources
     * @Serializer\Exclude
     */
    private NodesSources $nodesSource;
    /**
     * @var Settings
     * @Serializer\Exclude
     */
    private Settings $settingsBag;
    /**
     * @var UrlGeneratorInterface
     * @Serializer\Exclude
     */
    private UrlGeneratorInterface $urlGenerator;
    /**
     * @var NodeSourceApi
     * @Serializer\Exclude
     */
    private NodeSourceApi $nodeSourceApi;

    /**
     * @param NodesSources $nodesSource
     * @param Settings $settingsBag
     * @param UrlGeneratorInterface $urlGenerator
     * @param NodeSourceApi $nodeSourceApi
     */
    public function __construct(
        NodesSources $nodesSource,
        Settings $settingsBag,
        UrlGeneratorInterface $urlGenerator,
        NodeSourceApi $nodeSourceApi
    ) {
        $this->nodesSource = $nodesSource;
        $this->settingsBag = $settingsBag;
        $this->urlGenerator = $urlGenerator;
        $this->nodeSourceApi = $nodeSourceApi;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single"})
     * @Serializer\VirtualProperty
     */
    public function getGoogleAnalytics(): ?string
    {
        return $this->settingsBag->get('universal_analytics_id', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single"})
     * @Serializer\VirtualProperty
     */
    public function getGoogleTagManager(): ?string
    {
        return $this->settingsBag->get('google_tag_manager_id', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single"})
     * @Serializer\VirtualProperty
     */
    public function getSiteName(): ?string
    {
        // site_name
        return $this->settingsBag->get('site_name', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single"})
     * @Serializer\VirtualProperty
     */
    public function getPolicyUrl(): ?string
    {
        $policyNodeSource = $this->nodeSourceApi->getOneBy([
            'node.nodeName' => 'privacy',
            'translation' => $this->nodesSource->getTranslation()
        ]);
        if (null === $policyNodeSource) {
            $policyNodeSource = $this->nodeSourceApi->getOneBy([
                'node.nodeName' => 'legal',
                'translation' => $this->nodesSource->getTranslation()
            ]);
        }
        if (null !== $policyNodeSource) {
            return $this->urlGenerator->generate(RouteObjectInterface::OBJECT_BASED_ROUTE_NAME, [
                RouteObjectInterface::ROUTE_OBJECT => $policyNodeSource
            ]);
        }
        return null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single"})
     * @Serializer\VirtualProperty
     */
    public function getMainColor(): ?string
    {
        return $this->settingsBag->get('main_color', null) ?? null;
    }

    /**
     * @return Document|null
     * @Serializer\Groups({"nodes_sources_single"})
     * @Serializer\VirtualProperty
     */
    public function getShareImage(): ?Document
    {
        if (null !== $this->nodesSource &&
            method_exists($this->nodesSource, 'getHeaderImage') &&
            isset($this->nodesSource->getHeaderImage()[0])) {
            return $this->nodesSource->getHeaderImage()[0];
        }
        if (null !== $this->nodesSource &&
            method_exists($this->nodesSource, 'getImage') &&
            isset($this->nodesSource->getImage()[0])) {
            return $this->nodesSource->getImage()[0];
        }
        return $this->settingsBag->getDocument('share_image') ?? null;
    }
}
