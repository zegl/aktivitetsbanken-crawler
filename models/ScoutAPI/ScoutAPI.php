<?php

namespace ScoutAPI;

class ScoutAPI
{
	public $url = "http://infinite-forest-4832.herokuapp.com/api/v1/";

	public function api($method, $url, $data = null, $allow_cache = true)
	{
		$http = new \HTTP();
		$http->url($this->url . $url);

		if (!$allow_cache) {
			$http->disable_cache();
		}

		if ($data) {
			$http->data($data, "application/json");
		}

		if ($method === "PUT") {
			$http->method($method);
		}

		$http->run();

		if ($http->get_response_code() === 404) {
			var_dump($method, $url, $data, $allow_cache);
			die();
		}

		return [$http->get_response_code(), $http->get_json()];
	}
}