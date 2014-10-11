<?php

require_once 'SQL.php';

class SQLite extends SQL
{
    /**
	 * SQLite::_connection()
	 * @access public
	 */
    public function _connection()
    {
        if ($this->conn) {
            return $this->conn;
        }

        $this->conn = new SQLite3(realpath('db/database.sqlite'));

        return $this->conn;
    }

    /**
	 * SQLite::_escape()
	 * @access public
	 */
    public function _escape($v)
    {
        return $this->conn->escapeString($v);
    }

    private function _result_fetch($setting, $data, $single = false)
    {
        $res = [];

        while ($d = $data->fetchArray($setting)) {

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
        return $this->_result_fetch(SQLITE3_ASSOC, $data, $single);
    }

    public function result_array($data, $single = false)
    {
        return $this->_result_fetch(SQLITE3_NUM, $data, $single);
    }

    public function result_insert_id()
    {
        return $this->_connection()->lastInsertRowID();
    }

    public function result_affected_rows()
    {
        return $this->_connection()->changes();
    }
}
