<?php

class DB
{
	private $db;
	
	public function __construct()
	{
		require_once 'MySQL.php';
		$this->db = new MySQL();
	}

	public function insert($table, $values)
	{
		return $this->db->insert($table, $values);
	}

	public function truncate($table)
	{
		$this->db->truncate($table);
	}
}