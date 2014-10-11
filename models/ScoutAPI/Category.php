<?php

namespace ScoutAPI;

require_once 'ScoutAPI.php';
require_once 'Type.php';

class Category extends Type
{
    public $unique = "name";
    public $api_key = "categories";

    public $keys = [
        "name" => "default",
        "group" => ""
    ];
}
