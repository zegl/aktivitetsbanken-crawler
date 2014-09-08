<?php

class ScoutAPI
{
	private $url = "http://infinite-forest-4832.herokuapp.com/api/v1/";

	private function api($method, $url, $data = null, $allow_cache = true)
	{
		$http = new HTTP();
		$http->url($this->url . $url);

		if (!$allow_cache) {
			$http->cache_path = false;
		}

		if ($data) {
			$http->data($data);
		}

		if ($method === "PUT") {
			$http->method($method);
		}

		$http->run();
		return [$http->get_response_code(), $http->get_json()];
	}

	public function activity_save($json) {

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
			"time_min" => 0
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

		if ($this->activity_exists($json['name'])) {
			return $this->activity_update($json);
		}

		return $this->activity_create($json);
	}

	private $all_activities = [];
	public function activity_exists($name)
	{
		if (isset($this->all_activities[$name])) {
			return $this->all_activities[$name];
		}

		if ($this->all_activities !== []) {
			return false;
		}

		list($code, $res) = $this->api("GET", "activities", null, false);

		foreach ($res as $v) {
			$this->all_activities[$v['name']] = $v['id'];
		}

		if (isset($this->all_activities[$name])) {
			return $this->all_activities[$name];
		}

		return false;
	}

	/**
	 *		PUT http://127.0.0.1:3000/api/v1/activities/11 HTTP/1.1
	 *		Content-Type: application/json
	 *		{
	 *		   "name": "Updated Test Activity",
	 *		   "descr_introduction": null,
	 *		   "descr_main": "This is how you do...",
	 *		   "descr_material": "Rope, ...",
	 *		   "descr_notes": "Remember to...",
	 *		   "descr_prepare": "Before you start, ensure that...",
	 *		   "descr_safety": "It is important not to...",
	 *		   "age_max": 25,
	 *		   "age_min": 19,
	 *		   "participants_max": 10,
	 *		   "participants_min": 5,
	 *		   "time_max": 60,
	 *		   "time_min": 30,
	 *		   "categories": [],
	 *		   "references": []
	 *		}
	 */
	public function activity_update($json) {
		$id = $this->activity_exists($json['name']);

		list($code, $res) = $this->api('PUT', 'activities/' . $id, $json);

		if ($code === 204) {
			return true;
		}

		return false;
	}

	/**
	 *		POST http://127.0.0.1:3000/api/v1/activities HTTP/1.1
	 *		Content-Type: application/json
	 *		{
	 *		   "name": "Test Activity",
	 *		   "descr_introduction": null,
	 *		   "descr_main": "This is how you do...",
	 *		   "descr_material": "Rope, ...",
	 *		   "descr_notes": "Remember to...",
	 *		   "descr_prepare": "Before you start, ensure that...",
	 *		   "descr_safety": "It is important not to...",
	 *		   "age_max": 25,
	 *		   "age_min": 19,
	 *		   "participants_max": 10,
	 *		   "participants_min": 5,
	 *		   "time_max": 60,
	 *		   "time_min": 30,
	 *		   "categories": [],
	 *		   "references": []
	 *		}
	 */
	public function activity_create($json)
	{
		return $this->api('POST', 'activities', $json);
	}
}