<?php
ini_set('memory_limit', '256M');
require_once __DIR__ . '/vendor/autoload.php';
require('src/Crawler.php');
require('src/Site.php');
require('src/SiteFactory.php');

use SevenEcks\StringUtils\StringUtils;
use SevenEcks\Ansi\Colorize;
use SevenEcks\Web\Crawler;
use SevenEcks\Web\SiteFactory;
// load env vars
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// format strings nicely
$su = new StringUtils;

// Get the crawler
$crawler = new Crawler;

if (!$argv[1]) {
    return $su->tell(Colorize::red('Usage:') . ' php ' . $argv[0] . ' http://example.com');
}
$url = $argv[1];
$su->tell($su->tostr(Colorize::cyan('Beginning Crawl of URL: '), Colorize::yellow($url)));

$crawler->start($url);
