<?php

class DB
{
    private $db;

    public function __construct()
    {
        require_once 'db/SQLite.php';
        $this->db = new SQLite();
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->db, $method), $args);
    }
}
