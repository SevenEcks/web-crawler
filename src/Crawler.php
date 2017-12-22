<?php
namespace SevenEcks\Web;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use SevenEcks\StringUtils\StringUtils;
use SevenEcks\Ansi\Colorize;
use SevenEcks\Web\Site;
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
        $this->siteFactory = new SiteFactory;
    }

    public function start(string $url)
    {
        $starting_url = $this->siteFactory->newSite($url);
        $this->starting_url = $starting_url;
        $this->enqueueUrl($starting_url);
        $this->crawl();
    }

    public function tellCrawlUrl(string $url)
    {
        $this->su->tell('[' . Colorize::blue('CRAWLING') . '] ' . $url);
    }

    public function tellDiscoveredUrl(string $url)
    {
        $this->su->tell('[' . Colorize::green('DISCOVERED') . '] ' . $url);
    }

    public function crawl()
    {
        if (!($url = $this->dequeueUrl())) {
            $this->su->alert('Finished Crawling.');
            print_r($this->bad_urls);
            foreach ($this->bad_urls as $url => $sites) {
                foreach ($sites as $site) {
                    echo $url . "\n";
                    echo $site->getUrl() . "\n";
                }
            }
            return;
        } 
        // keep track of the urls
        $this->previous_url = $this->current_url;
        $this->current_url = $url;
        $this->su->alert('Crawling URL: ' . $url); 
        try {
            $response = $this->guzzle->get($url->getUrl()); 
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

    private function addBadUrl(Site $url, $status_code)
    {
        //$this->su->tell($this->su->tostr(Colorize::red("Bad URL ["), Colorize::yellow($status_code), Colorize::red("]"), " => ", $url));
        $this->su->critical('Bad URL [' . Colorize::yellow($status_code) .'] => ' . $url);
        // add this URL to the array of bad urls and track the current url it was found on
        $temp_array = [];
        if (isset($this->bad_urls[$url->getUrl()])) {
            $temp_array = $this->bad_urls[$url->getUrl()];
        }
        // TODO: confirm this is supposed to be previous url?
        $temp_array[] = $this->previous_url;
        $this->bad_urls[$url->getUrl()] = $temp_array;
    }

    private function isContentTypeHtml($content_type)
    {
        return strpos($content_type, "text/html");
    }


    private function sameDomain(Site $url_one, Site $url_two)
    {
        $host_one = parse_url($url_one->getUrl(), PHP_URL_HOST);
        $host_two = parse_url($url_two->getUrl(), PHP_URL_HOST);
        //echo 'Same domain check: ' . $url_one .  ' - ' . $url_two . ' ' . $host_one . ' ' . $host_two;
        return $host_one === $host_two;
    }

    private function addVisited(Site $url)
    {
        array_push($this->visited_urls, $url);
    }

    private function findUrls($body)
    {
        preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $body, $matches);
        foreach ($matches[0] as $match) {
            $url = $this->siteFactory->newSite($match, $this->current_url);
            if (!$this->enqueueUrl($url)) {
                continue;
            }
            $this->tellDiscoveredUrl($url);
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
                $this->su->warning('Ignoring special link: ' . $link);
                continue;
            }
            $url_pieces = parse_url($link);
            if (!$url_pieces['host']) {
                // local url
                $temp_starting_url = parse_url($this->starting_url);
                $link = $temp_starting_url['host'] . $url_pieces['path'];
            }
            $url = $this->siteFactory->newSite($link, $this->current_url);
            if (!$this->enqueueUrl($url)) {
                continue;
            }
            $this->tellDiscoveredUrl($url);
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

    private function enqueueUrl(Site $url)
    {
        // check that we haven't already visited or enqueued this link
        foreach ($this->visited_urls as $visited_url) {
            if ($visited_url->getUrl() == $url->getUrl()) {
                return;
            }
        }
        foreach ($this->pending_urls as $pending_url) {
            if ($pending_url->getUrl() == $url->getUrl()) {
                return;
            }
        }
        return array_push($this->pending_urls, $url);
    } 
}
