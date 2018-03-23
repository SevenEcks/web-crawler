<?php
namespace SevenEcks\Web;

use SevenEcks\Web\Page;

/**
 * Track a link from one page to another.
 *
 * @author Brendan Butts <bbutts@stormcode.net>
 */
class Link {
    private $from_page_url = null;
    private $to_page_url = null;

    /**
     * Construct a new link object from two page urls
     *
     * @param string $from_page_url
     * @param string $to_page_url
     * @return void
     */
    public function __construct(string $from_page_url, string $to_page_url)
    {
        $this->from_page_url = $from_page_url;
        $this->to_page_url = $to_page_url;
    }

    /**
     * Getter for private var $from_page_url
     *
     * @return mixed
     */
    public function getFromPageUrl()
    {
        return $this->from_page_url;
    }

    /**
     * Getter for private var $to_page_url
     *
     * @return mixed
     */
    public function getToPageUrl()
    {
        return $this->to_page_url;
    }

    /**
     * Does this Link have a fromPage set?
     *
     * @return bool
     */
    public function hasFromPageUrl()
    {
        return !is_null($this->from_page_url);
    }

    /**
     * Does this Link have a to_page_url set?
     *
     * @return bool
     */
    public function hasToPageUrl()
    {
        return !is_null($this->to_page_url);
    }
}
