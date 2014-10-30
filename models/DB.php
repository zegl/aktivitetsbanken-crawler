<?php

class DB
{
    private $db;

    public function __construct()
    {
        require_once 'db/SQLite.php';
        // require_once 'db/MySQL.php';

        $this->db = new SQLite();
        // $this->db = new MySQL();
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->db, $method), $args);
    }
}
