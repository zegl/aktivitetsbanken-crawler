<?php

error_reporting(E_ALL);
ini_set('Display_errors', 'On');

require_once 'models/HTTP.php';
require_once 'models/DB.php';
require_once 'models/Common.php';
require_once 'models/Tags.php';
require_once 'models/Activity.php';

$tags = new Tags();
$tags->tags();
