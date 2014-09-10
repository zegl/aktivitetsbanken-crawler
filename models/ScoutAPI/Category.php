<?php

namespace ScoutAPI;
require_once 'ScoutAPI.php';

class Category extends ScoutAPI
{
	public function save($json) {

		if (!isset($json['name'])) {
			return false;
		}

		$keys = [
			"name" => "default",
			"group" => ""
		];

		foreach ($json as $k => $v) {
			if (!isset($keys[$k])) {
				unset($json[$k]);
			}
		}

		foreach ($keys as $key => $default) {
			if (!isset($json[$key])) {
				$json[$key] = $default;
			}
		}

		if ($this->exists($json['name'])) {
			return $this->update($json);
		}

		return $this->create($json);
	}

	private static $category_all = false
	public function exists($name)
	{
		if (isset(self::$category_all[$name])) {
			return self::$category_all[$name];
		}

		if (self::$category_all !== false) {
			return false;
		}

		list($code, $res) = $this->api("GET", "categories", null, false);

		self::$category_all = [];
		foreach ($res as $v) {
			self::$category_all[$v['name']] = $v['id'];
		}

		if (isset(self::$category_all[$name])) {
			return self::$category_all[$name];
		}

		return false;
	}

	public function update($json) {

		return;

		$id = $this->exists($json['name']);

		// Only override the name value
		$json = [
			'name' => $json['name']
		];

		list($code, $res) = $this->api('PUT', 'categories/' . $id, $json);

		if ($code === 204) {
			return true;
		}

		return false;
	}

	public function create($json)
	{
		$res = $this->api('POST', 'categories', $json);
		self::$category_all[$res['name']] = $res['id'];
		return $res;
	}
}