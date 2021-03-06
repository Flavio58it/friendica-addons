<?php
/**
 * Name: blockbot
 * Description: Blocking bots based on detecting bots/crawlers/spiders via the user agent and http_from header.
 * Version: 0.1
 * Author: Philipp Holzer <admin@philipp.info>
 *
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\System;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Friendica\Core\Logger;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

function blockbot_install() {
	Hook::register('init_1', __FILE__, 'blockbot_init_1');
}


function blockbot_uninstall() {
	Hook::unregister('init_1', __FILE__, 'blockbot_init_1');
}

function blockbot_init_1(App $a) {
	if (empty($_SERVER['HTTP_USER_AGENT'])) {
		return;
	}

	$logdata = ['agent' => $_SERVER['HTTP_USER_AGENT'], 'uri' => $_SERVER['REQUEST_URI']];

	// List of known crawlers.
	$agents = ['SemrushBot', 's~feedly-nikon3', 'Qwantify/Bleriot/', 'ltx71', 'Sogou web spider/',
		'Diffbot/', 'Twitterbot/', 'YisouSpider', 'evc-batch/', 'LivelapBot/', 'TrendsmapResolver/',
		'PaperLiBot/', 'Nuzzel', 'um-LN/', 'Google Favicon', 'Datanyze', 'BLEXBot/', '360Spider',
		'adscanner/', 'HeadlessChrome', 'wpif', 'startmebot/', 'Googlebot/', 'Applebot/',
		'facebookexternalhit/', 'GoogleImageProxy', 'bingbot/', 'heritrix/', 'ldspider',
		'AwarioRssBot/', 'Zabbix', 'TweetmemeBot/', 'dcrawl/', 'PhantomJS/', 'Googlebot-Image/',
		'CrowdTanglebot/', 'Mediapartners-Google', 'Baiduspider/', 'datagnionbot',
		'MegaIndex.ru/', 'SMUrlExpander', 'Hatena-Favicon/', 'Wappalyzer', 'FlipboardProxy/',
		'NetcraftSurveyAgent/', 'Dataprovider.com', 'SMTBot/', 'Nimbostratus-Bot/',
		'DuckDuckGo-Favicons-Bot/', 'IndieWebCards/', 'proximic', 'netEstate NE Crawler',
		'AhrefsBot/', 'YandexBot/', 'Exabot/', 'Mediumbot-MetaTagFetcher/', 'WhatsApp/',
		'TelegramBot', 'SurdotlyBot/', 'BingPreview/', 'SabsimBot/', 'CCBot/', 'WbSrch/',
		'DuckDuckBot-Https/', 'HTTP Banner Detection', 'YandexImages/', 'archive.org_bot',
		'ArchiveTeam ArchiveBot/', 'yacybot', 'https://developers.google.com/+/web/snippet/',
		'Scrapy/', 'github-camo', 'MJ12bot/', 'DotBot/', 'Pinterestbot/', 'Jooblebot/',
		'Cliqzbot/', 'YaK/', 'Mediatoolkitbot'];

	foreach ($agents as $agent) {
		if (stristr($_SERVER['HTTP_USER_AGENT'], $agent)) {
			System::httpExit(403, 'Bots are not allowed');
		}
	}

	// This switch here is only meant for developers who want to add more bots to the list above, it is not safe for production.
	if (!Config::get('blockbot', 'training')) {
		return;
	}

	$crawlerDetect = new CrawlerDetect();

	if (!$crawlerDetect->isCrawler()) {
		logger::debug('Good user agent detected', $logdata);
		return;
	}

	// List of false positives' strings of known "good" agents.
	$agents = ['fediverse.network crawler', 'Active_Pods_CheckBot_3.0', 'Social-Relay/',
		'curl', 'zgrab', 'Go-http-client', 'curb', 'github.com', 'reqwest', 'Feedly/',
		'Python-urllib/', 'Liferea/', 'aiohttp/', 'WordPress.com Reader', 'hackney/',
		'Faraday v', 'okhttp', 'UniversalFeedParser', 'PixelFedBot', 'python-requests',
		'WordPress/', 'http.rb/', 'Apache-HttpClient/', 'WordPress.com;', 'Pleroma',
		'Dispatch/', 'Ruby', 'Uptimebot/', 'Java/', 'libwww-perl/', 'Mastodon/',
		'lua-resty-http/', 'Test Certificate Info'];

	foreach ($agents as $agent) {
		if (stristr($_SERVER['HTTP_USER_AGENT'], $agent)) {
			logger::notice('False positive', $logdata);
			return;
		}
	}

	logger::info('Blocked bot', $logdata);
	System::httpExit(403, 'Bots are not allowed');
}
