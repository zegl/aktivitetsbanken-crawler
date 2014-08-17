<?php

class MySQL
{
	private $conn;

	public static $allowed_variables = [
		"CURRENT_TIMESTAMP" => true
	];

	public function __construct()
	{
		$this->conn = $this->_connection();
	}

	public function _connection()
	{
		if ($this->conn) {
			return $this->conn;
		}

		$this->conn = new MySQLi('localhost', 'root', '', 'aktivitetsbanken');
		$this->conn->set_charset('utf8');

		return $this->conn;
	}

	public function _escape($v)
    {
        return $this->_connection()->real_escape_string($v);
    }

    private function _format($query, $args = array(), $escape = true)
    {
        // Remove first arg
        $args = array_slice($args, 1);

        if (empty($args)) {
            return $query;
        }

        if ($escape) {
            foreach ($args as $k => $v) {
                $args[$k] = $this->_escape($v);
            }
        }

        return vsprintf($query, $args);
    }

    private function _query($sql)
    {
    	return $this->_connection()->query($sql);
    }

	public function insert($table = false, $data = [])
    {
        if (!$table || empty($data)) {
            return false;
        }

        $replacements = array('', $table);

        $sql = "INSERT INTO %s (";

            $a = array();
            foreach ($data as $k => $v) {
                $replacements[] = $k;
                $a[] = "%s";
            }

            $sql .= "`" . implode('`,`', $a) . "`";

        $sql .= ") VALUES (";

            $a = array();
            foreach ($data as $k => $v) {

                if (isset(self::$allowed_variables[$v])) {
                    $a[] = $v;
                } else {
                    $replacements[] = $v;
                    $a[] = "'%s'";
                }
            }

            $sql .= implode(',', $a);

        $sql .= ")";

        $query = $this->_format($sql, $replacements);

        $data = $this->_query($query);

        if ($data === false) {
            return false;
        }

        return $this->_connection()->insert_id;
    }

    public function truncate($table)
    {
    	$sql = "TRUNCATE TABLE %s";
    	$query = $this->_format($sql, ['', $table]);

    	return $this->_query($query);
    }
}