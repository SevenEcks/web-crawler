<?php
namespace SevenEcks\Web;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use SevenEcks\StringUtils\StringUtils;
use SevenEcks\Ansi\Colorize;
use SevenEcks\Web\Page;
use SevenEcks\Xlog\Logger;
use SevenEcks\Web\PageFactory;
use SevenEcks\Web\LinkFactory;
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
    private $crawl_external = true;
    private $logging = true;

    /**
     * Instantiate the dependencies
     * @TODO make this use dependency injection
     * @return void
     */
    public function __construct()
    {
        $this->guzzle = new \GuzzleHttp\Client;
        $this->su = new StringUtils;
        $this->pageFactory = new PageFactory;
        $this->linkFactory = new LinkFactory;
        $this->logger = new Logger;
        $this->colorize = new Colorize;
    }

    /**
     * Set logging on or off
     *
     * @param bool $toggle
     * @return void
     */
    public function toggleLogging(bool $toggle)
    {
        $this->logging = $toggle;
    }

    /**
     * Return the status of logging being enabled or disabled
     *
     * @return bool
     */
    public function isLoggingEnabled()
    {
        return $this->logging;
    }

    /**
     * Setter for toggling external crawling on or off
     *
     * @param bool $new_value
     * @return void
     */
    public function setCrawlExternal(bool $new_value)
    {
        $this->crawl_external = $new_value;
    }

    /**
     * Start crawling
     *
     * @param string $url
     * @return void
     */
    public function start(string $url)
    {
        $starting_url = $this->pageFactory->newPage($url);
        $this->starting_url = $starting_url;
        $this->enqueueUrl($starting_url);
        $this->crawl();
    }

    /**
     * Print out the URL we are currently crawling
     *
     * @param string $url
     * @return void
     */
    public function tellCrawlUrl(string $url)
    {
        $this->su->tell('[' . $this->colorize->blue('CRAWLING') . '] ' . $url);
    }

    /**
     * Print out a discovered URL
     *
     * @param string $url
     * @return void
     */
    public function tellDiscoveredUrl(string $url)
    {
        $this->su->tell('[' . $this->colorize->green('DISCOVERED') . '] ' . $url);
    }

    /**
     * Log bad URLs to the log file
     *
     * @return void
     */
    private function logBadUrls()
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }
        $this->logger->warning($this->su->center('<Bad Urls>', null, '#'));
        foreach ($this->bad_urls as $url => $pages) {
            foreach ($pages as $page) {
                $this->logger->info($url);
                $this->logger->info($page->getUrl());
            }
        }
        $this->logger->warning($this->su->center('</End Bad Urls>', null, '#'));
    }
    /**
     * Dequeue a new URL and crawl it
     *
     * @return void
     */
    public function crawl()
    {
        if (!($url = $this->dequeueUrl())) {
            $this->su->alert('Finished Crawling.');
            $this->su->alert('Bad Urls Found: ' . count($this->bad_urls));
            $this->logBadUrls();
            $this->logAllUrls();
            return;
        } 
        // check if we should crawl external URLs
        if (!$this->crawl_external && !$this->sameDomain($url, $this->starting_url)) {
            return $this->crawl();
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

    /**
     * Log all the URLs we crawled to the log file
     *
     * @return void
     */
    public function logAllUrls()
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }
        $this->logger->alert($this->su->center('<All URLs>', null, '#'));
        $page_urls = [];
        foreach ($this->pageFactory->getPages() as $page) {
            if (isset($page_urls[$page->getUrl()])) {
                $page_urls[$page->getUrl()] += 1;
            } else {
                $page_urls[$page->getUrl()] = 1;
            }
            $this->logger->info('URL: ' . $this->colorize->yellow($page));
            $this->logger->info('Linked From: ');
            foreach ($page->getLinkedFrom() as $linked_from) {
                $this->logger->notice('    ' . $this->colorize->white($linked_from->getFromPageUrl()));
            }
            $this->logger->info('Links To: ');
            foreach ($page->getLinksTo() as $links_to) {
                $this->logger->notice('    ' . $this->colorize->white($links_to->getToPageUrl()));
            }
        }
        foreach ($page_urls as $key => $value) {
            if ($value > 1) {
                $this->logger->critical($key . ' ' . $value);
            }
        } 
        $this->logger->alert($this->su->center('</All URLs>', null, '#'));
    }

    /**
     * Add a url to our list of bad URLs
     *
     * @param Page $url
     * @param int $status_code
     * @return void
     */
    private function addBadUrl(Page $url, $status_code)
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

    /**
     * Check if the content_type is set to text/html
     *
     * @param string $content_type
     * @return void
     */
    private function isContentTypeHtml($content_type)
    {
        return strpos($content_type, "text/html");
    }

    /**
     * Check if two urls are the same base domain
     *
     * @param Page $url_one
     * @param Page $url_two
     * @return bool
     */
    private function sameDomain(Page $url_one, Page $url_two)
    {
        $host_one = parse_url($url_one->getUrl(), PHP_URL_HOST);
        $host_two = parse_url($url_two->getUrl(), PHP_URL_HOST);
        //echo 'Same domain check: ' . $url_one .  ' - ' . $url_two . ' ' . $host_one . ' ' . $host_two;
        return $host_one === $host_two;
    }

    /**
     * Add a page to the visited array
     *
     * @param Page $url
     * @return void
     */
    private function addVisited(Page $url)
    {
        array_push($this->visited_urls, $url);
    }

    /**
     * Log a special link
     *
     * @param string $link
     * @return void
     */
    public function logSpecialLink(string $link)
    {
        if (!$this->isLoggingEnabled()) {
            $this->logger->alert('Special Link: ' . $link);
        }
    }

    /**
     * Find and enqueue all hrefs in a body using DOMDocument
     *
     * @param string $body
     * @return void
     */
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
                $this->logSpecialLink($link);
                continue;
            }
            $url_pieces = parse_url($link);
            if (!$url_pieces['host']) {
                // local url
                $temp_starting_url = parse_url($this->starting_url);
                $link = $temp_starting_url['host'] . $url_pieces['path'];
            }
            // get the new page (or an existing obj) from the pageFactory
            $found_page = $this->pageFactory->newPage($link, $this->current_url);
            // get the new link (or an existing obj) from the linkFactory
            $found_link = $this->linkFactory->newLink($this->current_url, $found_page);
            // add the new link as an outgoing link to the current page
            $this->current_url->addLinksTo($found_link);
            // add the new link as an incoming link from the current page, to the page we just found
            $found_page->addLinkedFrom($found_link);
            // enqueue this url for crawing if we haven't seen it before
            if (!$this->enqueueUrl($found_page)) {
                continue;
            }
            // tell the discovered url if we haven't seen it before
            $this->tellDiscoveredUrl($found_page);
        }
    }

    /**
     * check if a link is special IE  mailtos and tel numbers
     *
     * @param string $link
     * @return bool
     */
    private function isSpecialLink($link)
    {
        if (substr($link, 0, 7) == 'mailto:') {
            return true;
        } elseif (substr($link, 0, 4) == 'tel:') {
            return true;
        }
        return false;
    }

    /**
     * Dequeue a URL from the pending_urls array
     *
     * @return Page
     */
    private function dequeueUrl()
    {
        return array_shift($this->pending_urls);
    }

    /**
     * Enqueue a Page object in pending_urls if
     * it has not already been visited before and
     * is not already present
     *
     * @param Page $url
     * @return bool
     */ 
    private function enqueueUrl(Page $url)
    {
        // check that we haven't already visited or enqueued this link
        $url_string = $url->getUrl();
        foreach ($this->visited_urls as $visited_url) {
            if ($visited_url->getUrl() == $url_string) {
                return false;
            }
        }
        foreach ($this->pending_urls as $pending_url) {
            if ($pending_url->getUrl() == $url_string) {
                return false;
            }
        }
        $this->logger->alert('URL Enqueued: ' . $this->colorize->blue($url));
        return array_push($this->pending_urls, $url);
    } 
}
