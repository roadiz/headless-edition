<?php
declare(strict_types=1);

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Core\Entities\NodesSources;

final class NodesSourcesHead
{
    /**
     * @var NodesSources
     * @Serializer\Exclude
     */
    private NodesSources $nodesSource;

    /**
     * @var string|null
     * @Serializer\Groups({"nodes_sources_single"})
     */
    private ?string $googleAnalytics;
    /**
     * @var string|null
     * @Serializer\Groups({"nodes_sources_single"})
     */
    private ?string $googleTagManager;
    /**
     * @var string|null
     * @Serializer\Groups({"nodes_sources_single"})
     */
    private ?string $siteName;
    /**
     * @var string|null
     * @Serializer\Groups({"nodes_sources_single"})
     */
    private ?string $mainColor;

    /**
     * @param NodesSources $nodesSource
     */
    public function __construct(NodesSources $nodesSource)
    {
        $this->nodesSource = $nodesSource;
    }

    /**
     * @return string|null
     */
    public function getGoogleAnalytics(): ?string
    {
        return $this->googleAnalytics;
    }

    /**
     * @param string|null $googleAnalytics
     * @return NodesSourcesHead
     */
    public function setGoogleAnalytics(?string $googleAnalytics): NodesSourcesHead
    {
        $this->googleAnalytics = $googleAnalytics;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGoogleTagManager(): ?string
    {
        return $this->googleTagManager;
    }

    /**
     * @param string|null $googleTagManager
     * @return NodesSourcesHead
     */
    public function setGoogleTagManager(?string $googleTagManager): NodesSourcesHead
    {
        $this->googleTagManager = $googleTagManager;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSiteName(): ?string
    {
        return $this->siteName;
    }

    /**
     * @param string|null $siteName
     * @return NodesSourcesHead
     */
    public function setSiteName(?string $siteName): NodesSourcesHead
    {
        $this->siteName = $siteName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMainColor(): ?string
    {
        return $this->mainColor;
    }

    /**
     * @param string|null $mainColor
     * @return NodesSourcesHead
     */
    public function setMainColor(?string $mainColor): NodesSourcesHead
    {
        $this->mainColor = $mainColor;
        return $this;
    }
}
