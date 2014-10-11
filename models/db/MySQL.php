<?php

require_once 'SQL.php';

class MySQL extends SQL
{
    /**
	 * MySQL::_connection()
	 * @access public
	 */
    public function _connection()
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
	 * @access public
	 */
    public function _escape($v)
    {
        return $this->_connection()->real_escape_string($v);
    }

    private function _result_fetch($method, $data, $single = false)
    {
        $res = [];

        while ($d = $data->$method()) {

            if ($single) {
                return $d;
            }

            $res[] = $d;
        }

        if ($single) {
            return false;
        }

        return $res;
    }

    public function result_assoc($data, $single = false)
    {
        return $this->_result_fetch('fetch_assoc', $data, $single);
    }

    public function result_array($data, $single = false)
    {
        return $this->_result_fetch('fetch_array', $data, $single);
    }

    public function result_insert_id()
    {
        return $this->_connection()->insert_id;
    }

    public function result_affected_rows()
    {
        return $this->_connection()->affected_rows;
    }
}
