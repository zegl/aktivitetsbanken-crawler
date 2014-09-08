<?php

error_reporting(E_ALL);
ini_set('Display_errors', 'On');

require_once 'models/HTTP.php';
require_once 'models/DB.php';
require_once 'models/Common.php';
require_once 'models/Tags.php';
require_once 'models/Activity.php';

$db = new DB();

$db->truncate('attachments');

// $rows = $db->rows("SELECT handle FROM activities where handle = 'tipspromenad-i-allemansratt'");
$rows = $db->rows("SELECT handle FROM activities");

foreach ($rows as $k => $v) {

	if ($k < 560) {
		continue;
	}

    echo $k . '/' . count($rows) . ' - ' . $v['handle'] . "\n";
    $act = new Activity($v['handle']);
    $act->crawl();
    $act->save();
}

die();

asort(Activity::$types);
var_dump(Activity::$types);
