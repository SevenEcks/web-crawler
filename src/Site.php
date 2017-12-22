<?php
namespace SevenEcks\Web;

class Site
{
    private $url;
    private $detected_on = [];

    public function __construct(string $url = '')
    {
        $this->setUrl($url);
    }

    public function setUrl(string $url)
    {
        $this->url = $this->sanitizeUrl($url);
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function addDetectedOn($url)
    {
        $this->detected_on[] = $url;
    }

    public function getDetectedOn()
    {
        return $this->detected_on;
    } 

    public function __toString()
    {
        return $this->getUrl();
    }

    private function sanitizeUrl(string $url)
    {
        // since we are getting links from the page, it's possible
        // that someone put www.example.com instead of http://
        // which will mess up parse_url
        if (strpos($url, '//') === false) {
            $url = '//' . $url;
        }
        return $url;
    }

    /* public function displayTree($dash = "") */
    /* { */
    /*     $dash .= "-"; */
    /*     foreach ($this->detected_on as $site) { */
    /*         echo $dash . $this->url . "\n"; */
    /*         $site->displayTree($dash); */
    /*     } */
    /* } */
}
