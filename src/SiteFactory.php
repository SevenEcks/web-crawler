<?php
namespace SevenEcks\Web;

class SiteFactory
{
    private $sites = [];

    public function newSite(string $url, $detected_on = null)
    {
        if (!($site = $this->siteExists($url))) {
            $site = new Site($url);
            $this->addSite($site);
        }
        if (isset($detected_on)) {
            $site->addDetectedOn($detected_on);
        } 
        return $site;
    }

    public function siteExists($url)
    {
        foreach ($this->sites as $site) {
            if ($site->getUrl() == $url) {
                return $site;
            }
        }
        return false;
    }

    public function addSite($site)
    {
        $this->sites[] = $site;
    }
}
