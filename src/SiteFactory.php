<?php
namespace SevenEcks\Web;

/**
 *
 * This class is a factor that provides new or existing instances
 * of a Site object. It uses the URL to detect if the Site alreadyu
 * exists as an object
 *
 * @author Brendan Butts <bbutts@stormcode.net>
 */
class SiteFactory
{
    // private array of all site objects currently instantiated
    private $sites = [];

    /**
     * Retrun an instance of a Site object either from the pool
     * of existing sites, or by instantiating a new object
     *
     * @param string $url
     * @param obj $detected_on
     * @return obj $site
     */
    public function newSite(string $url, $detected_on = null)
    {
        if (!($site = $this->siteExists($url))) {
            $site = new Site($url);
            $this->addSite($site);
        }
        if (isset($detected_on)) {
      //      echo 'Adding detected on for ' . $site->getUrl() . ' for: ' . $detected_on->getUrl();
            $site->addDetectedOn($detected_on);
        } 
        return $site;
    }

    /**
     * Check if a site exists already based on the $url
     * provided
     *
     * @param string $url
     * @return mixed bool/obj
     */
    public function siteExists($url)
    {
        $temp_site = new Site($url);
        foreach ($this->sites as $site) {
            if ($site->getUrl() == $temp_site->getUrl()) {
                unset($temp_site);
                return $site;
            }
        }
        return false;
    }

    /**
     * Add a new site obj to the sites array
     *
     * @param obj $site
     * @return none
     */
    public function addSite($site)
    {
        $this->sites[] = $site;
    }

    /**
     * Getter for the $sites property
     * 
     * @return array
     */
    public function getSites()
    {
        return $this->sites;
    }
}
