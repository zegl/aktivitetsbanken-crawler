<?php

error_reporting(E_ALL);
ini_set('Display_errors', 'On');

require 'vendor/autoload.php';

require_once 'models/HTTP.php';
require_once 'models/DB.php';
require_once 'models/Common.php';
require_once 'models/Tags.php';
require_once 'models/Activity.php';

$db = new DB();

// Step 1 - Find all activities
$http = new HTTP();
$html = $http->url("http://www.scouterna.se/aktiviteter-och-lager/aktivitetsbanken/alla-aktiviteter/")->run()->get();

$matches = null;
preg_match_all('#<strong><a href="(.*?)aktivitetsbanken/aktivitet/(.*?)/">(.*?)</a></strong>#i', $html, $matches);

// Step 2 - Loop over all activities and crawl the content + attachments
foreach ($matches[0] as $k => $v) {

    echo ($k+1) . "/" . count($matches[0]) . " = " . $matches[2][$k] . "\n";

    $act = new Activity($matches[2][$k]);

    if (!$act->crawl()) {
        continue;
    }

    $act->save();
}

// Step 3 - Find all categories
$tags = new Tags();
$tags->tags();

// Step 4 - Finalize and save to ScoutAPI
$activities = $db->rows("SELECT handle FROM activities");
//$activities = $db->rows("SELECT handle FROM activities Where handle = '%s'", 'olika-aventyrsteman-for-hajken');

foreach ($activities as $k => $activity) {
    echo ($k+1) . "/" . count($activities) . " = " . $activity['handle'] . "\n";
    $act = new Activity($activity['handle']);
    $act->crawl();
    $act->save(true);
}
