<?php
declare(strict_types=1);

namespace App\Model;

use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\NodesSources;

final class NodesSourcesHeadFactory
{
    private Settings $settingsBag;

    /**
     * @param Settings $settingsBag
     */
    public function __construct(Settings $settingsBag)
    {
        $this->settingsBag = $settingsBag;
    }

    public function createForNodeSource(NodesSources $nodesSources): NodesSourcesHead
    {
        $head = new NodesSourcesHead($nodesSources);
        $head->setGoogleAnalytics($this->settingsBag->get('universal_analytics_id', null));
        $head->setGoogleTagManager($this->settingsBag->get('google_tag_manager_id', null));
        $head->setSiteName($this->settingsBag->get('site_name', null));
        $head->setMainColor($this->settingsBag->get('main_color', null));
        return $head;
    }
}
