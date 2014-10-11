<?php

namespace ScoutAPI;

require_once 'ScoutAPI.php';
require_once 'Type.php';

class Reference extends Type
{
    public $unique = "uri";
    public $api_key = "references";

    public $keys = [
        "uri" => "",
        "description" => ""
    ];
}
