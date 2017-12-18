<?php
namespace SevenEcks\Web;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use SevenEcks\StringUtils\StringUtils;
use SevenEcks\Ansi\Colorize;

/**
 *
 * @author Brendan Butts <bbutts@stormcode.net>
 *
 */
class Crawler
{
    private $pending_urls = [];
    private $visited_urls = [];
    private $bad_urls = [];
    private $starting_url = '';
    private $current_url = '';
    private $previous_url = '';

    public function __construct()
    {
        $this->guzzle = new \GuzzleHttp\Client;
        $this->su = new StringUtils;
    }

    public function start(string $url)
    {
        $this->starting_url = $url;
        $this->enqueueUrl($url);
        $this->crawl();
    }

    public function crawl()
    {
        if (!($url = $this->dequeueUrl())) {
            $this->su->tell('Finished Crawling.');
            print_r($this->bad_urls);
            return;
        } 
        // keep track of the urls
        $this->previous_url = $this->current_url;
        $this->current_url = $url;
        $this->su->tell('Crawling URL: ' . $url); 
        try {
            $response = $this->guzzle->get($url); 
        } catch (ClientException $e) {
            $response = $e->getResponse();
        } catch (RequestException $e) {
            $response = $e->getResponse();
        }
        $this->addVisited($url);
        $content_type = $response->getHeader('Content-Type')[0];

        if ($response->getStatusCode() != 200) {
            $this->addBadUrl($url, $response->getStatusCode());
        }
        /*
         * Check if URL is:
         *  - same domain
         *  - Content-Type: Text/Html
         *
         *  If it is, add any found URLs to queue
         *  If it is not, continue crawling
         */
        if (!($this->isContentTypeHtml($content_type) === false) && $this->sameDomain($this->starting_url, $url)) {
            // still may need to add 'seen' urls or get a duplicates
            // this finds urls in the body, can cause issues if there is javascript
            // $this->findUrls($response->getBody());
            // this finds href tags
            $this->findHrefs($response->getBody());
        }
        $this->crawl();
    }

    private function addBadUrl($url, $status_code)
    {
        $this->su->tell($this->su->tostr(Colorize::red("Bad URL ["), Colorize::yellow($status_code), Colorize::red("]"), " => ", $url));
        // add this URL to the array of bad urls and track the current url it was found on
        $temp_array = [];
        if (isset($this->bad_urls[$url])) {
            $temp_array = $this->bad_urls[$url];
        }
        $temp_array[] = $this->previous_url;
        $this->bad_urls[$url] = $temp_array;
    }

    private function isContentTypeHtml($content_type)
    {
        return strpos($content_type, "text/html");
    }

    private function sameDomain($url_one, $url_two)
    {
        // since we are getting links from the page, it's possible
        // that someone put www.example.com instead of http://
        // which will mess up parse_url
        if (strpos($url_two, '//') === false) {
            $url_two = '//' . $url_two;
        }

        $host_one = parse_url($url_one, PHP_URL_HOST);
        $host_two = parse_url($url_two, PHP_URL_HOST);
        //echo 'Same domain check: ' . $url_one .  ' - ' . $url_two . ' ' . $host_one . ' ' . $host_two;
        return $host_one === $host_two;
    }

    private function addVisited($url)
    {
        array_push($this->visited_urls, $url);
    }

    private function findUrls($body)
    {
        preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $body, $matches);
        foreach ($matches[0] as $match) {
            if (!$this->enqueueUrl($match)) {
                continue;
            }
            $this->su->tell($this->su->tostr(Colorize::cyan("Discovered URL: "), Colorize::yellow($match)));
        }
    }

    private function findHrefs($body)
    {
        $dom = new \DOMDocument;

        //Parse the HTML. The @ is used to suppress any parsing errors
        //that will be thrown if the $html string isn't valid XHTML.
        @$dom->loadHTML($body);

        //Get all links. You could also use any other tag name here,
        //like 'img' or 'table', to extract other tags.
        $links = $dom->getElementsByTagName('a');

        //Iterate over the extracted links and display their URLs
        foreach ($links as $link) {
            $link = $link->getAttribute('href');
            // some links are special like mailto and tel
            if ($this->isSpecialLink($link)) {
                $this->su->tell(Colorize::brown('Ignoring special link: ' . $link));
                continue;
            }
            $url_pieces = parse_url($link);
            if (!$url_pieces['host']) {
                // local url
                $temp_starting_url = parse_url($this->starting_url);
                $link = $temp_starting_url['host'] . $url_pieces['path'];
            }
            if (!$this->enqueueUrl($link)) {
                continue;
            }
            $this->su->tell($this->su->tostr(Colorize::cyan("Discovered URL: "), Colorize::yellow($link)));
        }
    }

    private function isSpecialLink($link)
    {
        if (substr($link, 0, 7) == 'mailto:') {
            return true;
        } elseif (substr($link, 0, 4) == 'tel:') {
            return true;
        }
        return false;
    }

    private function dequeueUrl()
    {
        return array_shift($this->pending_urls);
    }

    private function enqueueUrl($url)
    {
        // check that we haven't already visited or enqueued this link
        if (in_array($url, $this->visited_urls)) {
            return;
        } elseif (in_array($url, $this->pending_urls)) {
            return;
        }
        return array_push($this->pending_urls, $url);
    } 
}
