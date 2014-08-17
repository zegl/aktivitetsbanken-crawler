<?php

class MySQL
{
    private $conn;

    public static $allowed_variables = [
        "CURRENT_TIMESTAMP" => true
    ];

    /**
	 * MySQL::__construct()
	 * @access public
	 */
    public function __construct()
    {
        $this->conn = $this->_connection();
    }

    /**
	 * MySQL::insert()
	 * @access public
	 */
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

    /**
	 * MySQL::truncate()
	 * @access public
	 */
    public function truncate($table)
    {
        $sql = "TRUNCATE TABLE %s";
        $query = $this->_format($sql, ['', $table]);

        return $this->_query($query);
    }

    /**
	 * MySQL::val()
	 * @access public
	 */
    public function val($query)
    {
        $query = $this->_format($query, func_get_args());
        $res = $this->_query($query);

        if ($res === false) {
            return false;
        }

        $res = $res->fetch_array();

        return $res[0];
    }

    /**
	 * MySQL::row()
	 * @access public
	 */
    public function row($query)
    {
        $query = $this->_format($query, func_get_args());
        $data = $this->_query($query);

        if ($data === false) {
            return false;
        }

        $res = $data->fetch_assoc();
        if (!$res) {
            return false;
        }

        return $res;
    }

    /**
	 * MySQL::rows()
	 * @access public
	 */
    public function rows($query)
    {
        $query = $this->_format($query, func_get_args());
        $data = $this->_query($query);

        if ($data === false) {
            return false;
        }

        $res = array();

        while ($d = $data->fetch_assoc()) {
            $res[] = $d;
        }

        return $res;
    }

    /**
	 * MySQL::update()
	 * @access public
	 */
    public function update($query, $data = array())
    {
        $input = func_get_args();
        unset($input[1]); // $data

        $update = array();

        // Escape
        foreach ($data as $k => $v) {
            $data[$k] = $this->_escape($v);
        }

        foreach ($data as $k => $v) {

            if (isset(self::$allowed_variables[$v])) {
                $update[] = "`" . $k . "` = " . $v;
            } else {
                $update[] = "`" . $k . "` = '" . $v . "'";
            }
        }

        $update = implode(', ', $update);

        // _format()
        $args = array('', $update);

        // Add arguments
        foreach ($input as $k => $v) {
            if ($k < 2) {
                continue;
            }

            $args[] = $this->_escape($v);
        }

        // Format without _escape(), it's already taken care of
        $query = $this->_format($query, $args, false);

        // Run query
        $data = $this->_query($query);

        if ($data === false) {
            return false;
        }

        return $this->_connection()->affected_rows;
    }

    /**
	 * MySQL::_connection()
	 * @access private
	 */
    private function _connection()
    {
        if ($this->conn) {
            return $this->conn;
        }

        $this->conn = new MySQLi('localhost', 'root', '', 'aktivitetsbanken');
        $this->conn->set_charset('utf8');

        return $this->conn;
    }

    /**
	 * MySQL::_escape()
	 * @access private
	 */
    private function _escape($v)
    {
        return $this->_connection()->real_escape_string($v);
    }

    /**
	 * MySQL::_format()
	 * @access private
	 */
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

    /**
	 * MySQL::_query()
	 * @access private
	 */
    private function _query($sql)
    {
        return $this->_connection()->query($sql);
    }
}
