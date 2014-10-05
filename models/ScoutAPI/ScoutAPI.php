<?php

namespace ScoutAPI;

class ScoutAPI
{
	public $url = "http://infinite-forest-4832.herokuapp.com/api/v1/";
	public static $token = '80617507d3';

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

		// Authenticate
		if (self::$token) {
			$http->header('Authorization', 'Token token="' . self::$token . '"');
		}

		$http->run();

		if ($http->get_response_code() === 404) {
			var_dump($method, $url, $data, $allow_cache);
			die();
		}

		$json = $http->get_json();

		// Authentication has probably failed, register and try again
		if ($json === false) {
			$this->register();
			$this->api($method, $url, $data, $allow_cache);
		}

		return [$http->get_response_code(), $json];
	}

	private function register()
	{
		list($code, $response) = $this->api('POST', 'users', [
			"email" => "crawler@gustav.tv",
			"display_name" => "Crawler"
		]);

		if (!isset($response['api_key'])) {
			var_dump($response);
			die('Could not register account');
		}

		self::$token = $response['api_key'];
	}
}