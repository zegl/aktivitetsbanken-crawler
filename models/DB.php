<?php

class DB
{
    private $db;

    public function __construct()
    {
        require_once 'MySQL.php';
        $this->db = new MySQL();
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->db, $method), $args);
    }
}
