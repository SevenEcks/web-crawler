<?php
ini_set('memory_limit', '256M');
require_once __DIR__ . '/vendor/autoload.php';

require('src/Crawler.php');
require('src/Page.php');
require('src/PageFactory.php');
require('src/Link.php');
require('src/LinkFactory.php');
use SevenEcks\StringUtils\StringUtils;
use SevenEcks\Ansi\Colorize;
use SevenEcks\Web\Crawler;
use SevenEcks\Web\PageFactory;

// load env vars
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// format strings nicely
$su = new StringUtils;

// Get the crawler
$crawler = new Crawler;

// Clear the log
$crawler->logger->clearLog();

// if we don't have args, give usage
if (!$argv[1]) {
    return $su->alert('Usage: php ' . $argv[0] . ' http://example.com');
}
// get the url
$url = $argv[1];
// let the user know we are starting
$su->tell($su->tostr(Colorize::cyan('Beginning Crawl of URL: '), Colorize::yellow($url)));
// start the crawler
$crawler->setCrawlExternal(false);
$crawler->start($url);
