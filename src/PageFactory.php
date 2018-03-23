<?php
namespace SevenEcks\Web;

/**
 *
 * This class is a factory that provides new or existing instances
 * of a Page object. It uses the URL to detect if the Page already
 * exists as an object
 *
 * @author Brendan Butts <bbutts@stormcode.net>
 */
class PageFactory
{
    // private array of all page objects currently instantiated
    private $pages = [];

    /**
     * Retrun an instance of a Page object either from the pool
     * of existing pages, or by instantiating a new object
     *
     * @param string $url
     * @param obj $detected_on
     * @return Page $page
     */
    public function newPage(string $url, $detected_on = null)
    {
        if (!($page = $this->pageExists($url))) {
            $page = new Page($url);
            $this->addPage($page);
        }
        return $page;
    }

    /**
     * Check if a page exists already based on the $url
     * provided
     *
     * @param string $url
     * @return mixed bool/obj
     */
    public function pageExists($url)
    {
        $temp_page = new Page($url);
        foreach ($this->pages as $page) {
            if ($page->getUrl() == $temp_page->getUrl()) {
                unset($temp_page);
                return $page;
            }
        }
        return false;
    }

    /**
     * Add a new page obj to the pages array
     *
     * @param obj $page
     * @return none
     */
    public function addPage(Page $page)
    {
        $this->pages[] = $page;
    }

    /**
     * Getter for the $pages property
     * 
     * @return array
     */
    public function getPages()
    {
        return $this->pages;
    }
}
