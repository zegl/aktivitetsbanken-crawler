<?php

require_once 'models/HTTP.php';
require_once 'models/Activity.php';

$db = new DB();

//$db->truncate('activities');
//$db->truncate('activities_names');
//$db->truncate('attachments');
//$db->truncate('categories');

$http = new HTTP();
$html = $http->url("http://www.scouterna.se/aktiviteter-och-lager/aktivitetsbanken/alla-aktiviteter/")->run()->get();

$matches = null;
preg_match_all('#<strong><a href="(.*?)aktivitetsbanken/aktivitet/(.*?)/">(.*?)</a></strong>#i', $html, $matches);

foreach ($matches[0] as $k => $v) {

    var_dump($matches[2][$k]);

    $act = new Activity($matches[2][$k]);

    if (!$act->crawl()) {
        continue;
    }

    $act->save();
}
