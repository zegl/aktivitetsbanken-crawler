<?php

class Common
{
    public $db;

    public function __construct()
    {
        $this->db = new DB();
    }
}
