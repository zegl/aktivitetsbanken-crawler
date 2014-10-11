<?php

namespace ScoutAPI;

require_once 'ScoutAPI.php';
require_once 'Type.php';

class Activity extends Type
{
	public $unique = "name";
	public $api_key = "activities";

	public $keys = [
		"name" => "default",
		"descr_introduction" => "",
		"descr_main" => "",
		"descr_material" => "",
		"descr_notes" => "",
		"descr_prepare" => "",
		"descr_safety" => "", 
		"age_max" => 0,
		"age_min" => 0,
		"participants_max" => 0,
		"participants_min" => 0,
		"time_max" => 0,
		"time_min" => 0,
		"categories" => [],
		"references" => [],
		"media_files" => [],
	];
}