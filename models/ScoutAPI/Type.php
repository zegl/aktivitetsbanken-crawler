<?php

namespace ScoutAPI;

require_once 'ScoutAPI.php';

class Type extends ScoutAPI
{
    // Key, eg "name"
    public $unique;

    // [key => default, ... ]
    public $keys = [];

    // Api key, eg. "categories"
    public $api_key;

    public function save($array)
    {
        if (!isset($array[$this->unique])) {
            return false;
        }

        // Allowed keys
        foreach ($array as $k => $v) {
            if (!isset($this->keys[$k])) {
                unset($array[$k]);
            }
        }

        // Set defaults
        foreach ($this->keys as $key => $default) {
            if (!isset($array[$key])) {
                $array[$key] = $default;
            }
        }

        if ($this->exists($array[$this->unique])) {
            return $this->update($array);
        }

        return $this->create($array);
    }

    private static $all = [];

    public function exists($value)
    {
        $class = get_class($this);

        if (isset(self::$all[$class][$value])) {
            return self::$all[$class][$value];
        }

        if (isset(self::$all[$class])) {
            return false;
        }

        list($code, $res) = $this->api("GET", $this->api_key, null, false);

        self::$all[$class] = [];

        foreach ($res as $v) {
            self::$all[$class][$v[$this->unique]] = (int) $v['id'];
        }

        if (isset(self::$all[$class][$value])) {
            return self::$all[$class][$value];
        }

        return false;
    }

    public function update($array)
    {
        $id = $this->exists($array[$this->unique]);

        if ($id === false) {
            return false;
        }

        list($code, $res) = $this->api('PUT', $this->api_key . '/' . $id, $array);

        if ($code === 204) {
            return (int) $id;
        }

        return false;
    }

    public function create($data)
    {
        list($code, $res) = $this->api('POST', $this->api_key, $data);

        if ($code !== 201) {
            var_dump($code, $res, $data);
            die();
        }

        self::$all[get_class($this)][$res[$this->unique]] = (int) $res['id'];

        return (int) $res['id'];
    }
}
