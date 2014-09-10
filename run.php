<?php

error_reporting(E_ALL);
ini_set('Display_errors', 'On');

require_once 'models/HTTP.php';
require_once 'models/DB.php';
require_once 'models/Common.php';
require_once 'models/Tags.php';
require_once 'models/Activity.php';

$db = new DB();

$http = new HTTP();
$html = $http->url("http://www.scouterna.se/aktiviteter-och-lager/aktivitetsbanken/alla-aktiviteter/")->run()->get();

$matches = null;
preg_match_all('#<strong><a href="(.*?)aktivitetsbanken/aktivitet/(.*?)/">(.*?)</a></strong>#i', $html, $matches);

foreach ($matches[0] as $k => $v) {

    echo ($k+1) . "/" . count($matches[0]) . " = " . $matches[2][$k] . "\n";

    $act = new Activity($matches[2][$k]);

    if (!$act->crawl()) {
        continue;
    }

    $act->save();
}

die();

asort(Activity::$types);
var_dump(Activity::$types);