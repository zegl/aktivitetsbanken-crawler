<?php

namespace ScoutAPI;
require_once 'ScoutAPI.php';

class Activity extends ScoutAPI
{
	public function save($json) {

		if (!isset($json['name'])) {
			return false;
		}

		$keys = [
			"name" => "default",
			"descr_introduction" => "",
			"descr_main" => "",
			"descr_material" => "",
			"descr_notes" => "",
			"descr_prepare" => "",
			"descr_safety" => "", 
			"age_max" => 0,
			"age_min" => 0,
			"participants_max" => 0,
			"participants_min" => 0,
			"time_max" => 0,
			"time_min" => 0,
			"categories" => []
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

	private static $activity_all = false;
	public function exists($name)
	{
		if (isset(self::$activity_all[$name])) {
			return self::$activity_all[$name];
		}

		if (self::$activity_all !== false) {
			return false;
		}

		list($code, $res) = $this->api("GET", "activities", null, false);

		self::$activity_all = [];

		foreach ($res as $v) {
			self::$activity_all[$v['name']] = $v['id'];
		}

		if (isset(self::$activity_all[$name])) {
			return self::$activity_all[$name];
		}

		return false;
	}

	public function update($json) {
		$id = $this->exists($json['name']);

		list($code, $res) = $this->api('PUT', 'activities/' . $id, $json);

		if ($code === 204) {
			return true;
		}

		return false;
	}

	public function create($json)
	{
		$res = $this->api('POST', 'activities', $json);

		self::$activity_all[$res['name']] = $res['id'];

		return $res;

	}
}