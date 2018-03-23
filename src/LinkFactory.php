<?php
namespace SevenEcks\Web;

use SevenEcks\Web\Link;

class LinkFactory
{
    private $links = [];

    /**
     * Retrun an instance of a Link object either from the pool
     * of existing links, or by instantiating a new object
     *
     * @param Page $fromPage
     * @param Page $toPage
     * @return Link
     */
    public function newLink(Page $fromPage, Page $toPage)
    {
        if (!($link = $this->linkExists($fromPage, $toPage))) {
            $link = new Link($fromPage->getUrl(), $toPage->getUrl());
            $this->addLink($link);
        }
        return $link;
    }

    /**
     * Check if a link exists already based on the $fromPage and 
     * $toPage provided
     *
     * @param Page $fromPage
     * @param Page $toPage
     * @return mixed bool/page
     */
    private function linkExists(Page $fromPage, Page $toPage)
    {
        foreach ($this->links as $link) {
            if ($link->getFromPageUrl() == $fromPage->getUrl() && $link->getToPageUrl() == $toPage->getUrl()) {
                return $link;
            }
        }
        return false;
    }

    /**
     * Add a new link obj to the links array
     *
     * @param Link $link
     * @return none
     */
    private function addLink(Link $link)
    {
        $this->links[] = $link;
    }

    /**
     * Getter for the $links property
     * 
     * @return array
     */
    public function getLinks()
    {
        return $this->links;
    }
}
