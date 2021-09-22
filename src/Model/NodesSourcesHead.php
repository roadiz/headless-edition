<?php
declare(strict_types=1);

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\CMS\Utils\NodeSourceApi;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NodesSourcesHead
{
    /**
     * @var NodesSources|null
     * @Serializer\Exclude
     */
    private ?NodesSources $nodesSource;
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
     * @Serializer\Exclude
     */
    private Translation $defaultTranslation;

    /**
     * @param NodesSources|null $nodesSource
     * @param Settings $settingsBag
     * @param UrlGeneratorInterface $urlGenerator
     * @param NodeSourceApi $nodeSourceApi
     * @param Translation $defaultTranslation
     */
    public function __construct(
        ?NodesSources $nodesSource,
        Settings $settingsBag,
        UrlGeneratorInterface $urlGenerator,
        NodeSourceApi $nodeSourceApi,
        Translation $defaultTranslation
    ) {
        $this->nodesSource = $nodesSource;
        $this->settingsBag = $settingsBag;
        $this->urlGenerator = $urlGenerator;
        $this->nodeSourceApi = $nodeSourceApi;
        $this->defaultTranslation = $defaultTranslation;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single", "walker"})
     * @Serializer\SkipWhenEmpty
     * @Serializer\VirtualProperty
     */
    public function getGoogleAnalytics(): ?string
    {
        return $this->settingsBag->get('universal_analytics_id', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single", "walker"})
     * @Serializer\SkipWhenEmpty
     * @Serializer\VirtualProperty
     */
    public function getGoogleTagManager(): ?string
    {
        return $this->settingsBag->get('google_tag_manager_id', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single", "walker"})
     * @Serializer\SkipWhenEmpty
     * @Serializer\VirtualProperty
     */
    public function getMatomoUrl(): ?string
    {
        return $this->settingsBag->get('matomo_url', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single", "walker"})
     * @Serializer\SkipWhenEmpty
     * @Serializer\VirtualProperty
     */
    public function getMatomoSiteId(): ?string
    {
        return $this->settingsBag->get('matomo_site_id', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single", "walker"})
     * @Serializer\VirtualProperty
     */
    public function getSiteName(): ?string
    {
        // site_name
        return $this->settingsBag->get('site_name', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single", "walker"})
     * @Serializer\SkipWhenEmpty
     * @Serializer\VirtualProperty
     */
    public function getPolicyUrl(): ?string
    {
        $translation = $this->getTranslation();

        $policyNodeSource = $this->nodeSourceApi->getOneBy([
            'node.nodeName' => 'privacy',
            'translation' => $translation
        ]);
        if (null === $policyNodeSource) {
            $policyNodeSource = $this->nodeSourceApi->getOneBy([
                'node.nodeName' => 'legal',
                'translation' => $translation
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
     * @Serializer\Groups({"nodes_sources_single", "walker"})
     * @Serializer\SkipWhenEmpty
     * @Serializer\VirtualProperty
     */
    public function getMainColor(): ?string
    {
        return $this->settingsBag->get('main_color', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single", "walker"})
     * @Serializer\SkipWhenEmpty
     * @Serializer\VirtualProperty
     */
    public function getFacebookUrl(): ?string
    {
        return $this->settingsBag->get('facebook_url', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single", "walker"})
     * @Serializer\SkipWhenEmpty
     * @Serializer\VirtualProperty
     */
    public function getInstagramUrl(): ?string
    {
        return $this->settingsBag->get('instagram_url', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single", "walker"})
     * @Serializer\SkipWhenEmpty
     * @Serializer\VirtualProperty
     */
    public function getTwitterUrl(): ?string
    {
        return $this->settingsBag->get('twitter_url', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single", "walker"})
     * @Serializer\SkipWhenEmpty
     * @Serializer\VirtualProperty
     */
    public function getYoutubeUrl(): ?string
    {
        return $this->settingsBag->get('youtube_url', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single", "walker"})
     * @Serializer\SkipWhenEmpty
     * @Serializer\VirtualProperty
     */
    public function getLinkedinUrl(): ?string
    {
        return $this->settingsBag->get('linkedin_url', null) ?? null;
    }

    /**
     * @return string|null
     * @Serializer\Groups({"nodes_sources_single", "walker"})
     * @Serializer\VirtualProperty
     */
    public function getHomePageUrl(): ?string
    {
        $homePage = $this->getHomePage();
        if (null !== $homePage) {
            return $this->urlGenerator->generate(RouteObjectInterface::OBJECT_BASED_ROUTE_NAME, [
                RouteObjectInterface::ROUTE_OBJECT => $homePage
            ]);
        }
        return null;
    }

    /**
     * @return Document|null
     * @Serializer\Groups({"nodes_sources_single"})
     * @Serializer\SkipWhenEmpty
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

    private function getTranslation(): Translation
    {
        if (null !== $this->nodesSource) {
            return $this->nodesSource->getTranslation();
        }
        return $this->defaultTranslation;
    }

    private function getHomePage(): ?NodesSources
    {
        return $this->nodeSourceApi->getOneBy([
            'node.home' => true,
            'translation' => $this->getTranslation()
        ]);
    }
}
