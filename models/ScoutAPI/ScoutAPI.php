<?php

namespace ScoutAPI;

class ScoutAPI
{
	public $url = "http://aktivitetsbanken.devscout.se/api/v1/";
	public static $token = false;

	private function get_api_token()
	{
		if (self::$token) {
			return self::$token;
		}

		$dotenv = new \Dotenv\Dotenv(__DIR__ . "/../../");
		$dotenv->load();

		self::$token = getenv('API_TOKEN');

		return self::$token;
	}

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

		$http->method($method);

		// Authenticate
		$http->header('Authorization', 'Token token="' . $this->get_api_token() . '"');

		$http->run();

		if ($http->get_response_code() === 404) {
			var_dump($method, $url, $data, $allow_cache);
			die();
		}

		$json = $http->get_json();

		// Authentication has probably failed, register and try again
		if ($http->get_response_code() === 401) {
			var_dump("Got 401");
			var_dump($method, $url, $data, $allow_cache);
		}

		if ($json === false) {
			$json = $http->get();
		}

		return [$http->get_response_code(), $json];
	}
}