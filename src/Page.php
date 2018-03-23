<?php
namespace SevenEcks\Web;

/**
 * This class instantiates into a page object where the url of 
 * a specific page on a site and the pages linked from are stored
 *
 * @author Brendan Butts <bbutts@stormcode.net>
 */
class Page
{
    // url pieces for this page object
    private $scheme;
    private $host;
    private $port;
    private $user;
    private $pass;
    private $path;
    private $query;
    private $fragment;

    // pages we have detected that this page is linked to on
    private $linked_from = [];
    // pages that this page links out to
    private $links_to = [];

    /**
     * Constructor for the page object. Handles initial 
     * setup
     *
     * @param string $url
     * @return none
     */
    public function __construct(string $url = '')
    {
        $this->setUrl($url);
    }

    /**
     * Setter for the $url
     *
     * @param string $url
     * @return none
     */
    public function setUrl(string $url)
    {
        $sanitized_url = self::sanitizeUrl($url);
        $parsed_url = parse_url($sanitized_url);
        $this->scheme = $parsed_url['scheme'] ?? null;
        $this->host = $parsed_url['host'] ?? null;
        $this->port = $parsed_url['port'] ?? null;
        $this->user = $parsed_url['user'] ?? null;
        $this->password = $parsed_url['password'] ?? null;
        $this->path = $parsed_url['path'] ?? '/';
        $this->query = $parsed_url['query'] ?? null;
        $this->fragment = $parsed_url['fragment'] ?? null;
    }

    /**
     * Get the partial path or full path of the $url
     *
     * @param bool $full = true
     * @return string $url
     */
    public function getUrl(bool $full = true)
    {
        // only return //www.example.com/path?args=1&args=2
        if (!$full) {
            return '//' . $this->host . $this->path . (isset($this->query) ? '?' . $this->query : '');
        }
        // return full path include port and such
        return '//' . (isset($this->username) && isset($this->password) ? $this->username . ':' . $this->password . '@' : '') . $this->host . (isset($this->port) ? ':' . $this->port : '') . $this->path .  (isset($this->query) ? '?' . $this->query : '');
    }

    /**
     * Add a page that this page links to to the 
     * links_to array, if it isn't already there.
     *
     * @param Page $toPage
     * @return void
     */
    public function addLinksTo(Link $link)
    {
        if (!in_array($link, $this->links_to)) {
            $this->links_to[] = $link; 
        }
    }

    /**
     * Getter for the links_to private property
     *
     * @return array
     */
    public function getLinksTo()
    {
        return $this->links_to;
    }

    /**
     * Add a page that this page is linked from to to the 
     * linked_from array, if it isn't already there.
     *
     * @param Link $link
     * @return void
     */
    public function addLinkedFrom(Link $link)
    {
        if (!in_array($link, $this->linked_from)) {
            $this->linked_from[] = $link; 
        }
    }

    /**
     * Getter for the links_from private property
     *
     * @return array
     */
    public function getLinkedFrom()
    {
        return $this->linked_from;
    }

    /**
     * Allow this object to be converted to a string
     *
     * @return string $url
     */
    public function __toString()
    {
        return $this->getUrl();
    }

    /**
     * Turn any urls that don't have // in the into a
     * full url
     *
     * @param string $url
     * @return string
     */ 
    public static function sanitizeUrl(string $url)
    {
        // since we are getting links from the page, it's possible
        // that someone put www.example.com instead of http://
        // which will mess up parse_url
        if (strpos($url, '//') === false) {
            $url = '//' . $url;
        }
        return $url;
    }
}
