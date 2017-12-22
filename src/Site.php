<?php
namespace SevenEcks\Web;

/**
 * This class instantiates into a site object where the url of 
 * a specific page on a site and the pages linked from are stored
 *
 * @author Brendan Butts <bbutts@stormcode.net>
 */
class Site
{
    // url pieces for this site object
    private $scheme;
    private $host;
    private $port;
    private $user;
    private $pass;
    private $path;
    private $query;
    private $fragment;
    // array of $site objects that this site has been found on
    private $detected_on = [];

    /**
     * Constructor for the site object. Handles initial 
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
     * Get the privacy $url
     *
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
     * Setter for adding a detected_on $site 
     * object to the private prop
     *
     * @param obj $url
     * @return none
     */
    public function addDetectedOn(Site $url)
    {
        $test_url = $url->getUrl();
        foreach ($this->detected_on as $site) {
            if ($site->getUrl() == $test_url) {
                // already exists
                return;
            }
        } 
        $this->detected_on[] = $url;
    }

    /**
     * Get the detected on array
     *
     * @return array
     */
    public function getDetectedOn()
    {
        return $this->detected_on;
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
