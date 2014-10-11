<?php

namespace ScoutAPI;

require_once 'ScoutAPI.php';
require_once 'Type.php';

class Media_file extends Type
{
	public $unique = "uri";
	public $api_key = "media_files";

	public $keys = [
		"mime_type" => "",
		"uri" => ""
	];
}