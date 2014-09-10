<?php

class SQL
{
    public $conn;

    public static $allowed_variables = [
        "CURRENT_TIMESTAMP" => true
    ];

    /**
	 * SQL::__construct()
	 * @access public
	 */
    public function __construct()
    {
        $this->conn = $this->_connection();
    }

    /**
	 * SQL::insert()
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

        return $this->result_insert_id();
    }

    /**
	 * SQL::truncate()
	 * @access public
	 */
    public function truncate($table)
    {
        $sql = "DELETE FROM %s";
        $query = $this->_format($sql, ['', $table]);

        return $this->_query($query);
    }

    /**
	 * SQL::val()
	 * @access public
	 */
    public function val($query)
    {
        $query = $this->_format($query, func_get_args());
        $res = $this->_query($query);

        if ($res === false) {
            return false;
        }

        $res = $this->result_array($res, true);
        return $res[0];
    }

    /**
	 * SQL::row()
	 * @access public
	 */
    public function row($query)
    {
        $query = $this->_format($query, func_get_args());
        $data = $this->_query($query);

        if ($data === false) {
            return false;
        }

        return $this->result_assoc($data, true);
    }

    /**
	 * SQL::rows()
	 * @access public
	 */
    public function rows($query)
    {
        $query = $this->_format($query, func_get_args());
        $data = $this->_query($query);

        if ($data === false) {
            return false;
        }

        return $this->result_assoc($data);
    }

    /**
	 * SQL::update()
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

        return $this->result_affected_rows();
    }

    /**
	 * SQL::_format()
	 * @access private
	 */
    public function _format($query, $args = array(), $escape = true)
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
	 * SQL::_query()
	 * @access private
	 */
    public function _query($sql)
    {
        $t = microtime(true);
        $query = $this->_connection()->query($sql);
        $diff = microtime(true) - $t;

        if ($diff > 0.1) {
            echo "SQL $diff - $sql\n";
        }

        $err = false;

        // SQLite3
        if ($query === false) {
            $err = $this->_connection()->lastErrorMsg();
        }

        // MySQL
        if(isset($this->_connection()->errno) && $this->_connection()->errno) {
            $err = $this->_connection()->error;
        }

        if ($err !== false) {
            var_dump(debug_backtrace());
            var_dump($err);
            die();
        }

        return $query;
    }
}
