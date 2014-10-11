<?php

class HTTP
{

    private $ch;
    private $result;
    private $cache_path = null;

    private $headers = [];
    private $url;
    private $method = 'GET';

    public function __construct()
    {
        $this->ch = curl_init();

        return $this;
    }

    public function __destruct()
    {
        curl_close($this->ch);
    }

    public function url($url)
    {
        if ($this->cache_path !== false) {
            $this->cache_path = 'cache/' . md5($url);
        }

        $this->url = $url;
        curl_setopt($this->ch, CURLOPT_URL, $url);

        return $this;
    }

    public function disable_cache()
    {
        $this->cache_path = false;
    }

    public function data($data, $content_type = "application/x-www-form-urlencoded")
    {
        $this->cache_path = false;

        if ($content_type === "application/json") {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $this->header('Content-Type', $content_type);
        $this->method('POST');

        return $this;
    }

    public function method($method)
    {
        $this->method = $method;
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
    }

    public function run()
    {
        if ($this->cache_path && file_exists($this->cache_path)) {
            $this->result = file_get_contents($this->cache_path);

            echo "Cache -  $this->url\n";

            return $this;
        }

        $this->generate_headers();

        $t = microtime(true);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

        echo "$this->method - $this->url - ";

        $this->result = curl_exec($this->ch);

        echo (microtime(true) - $t) . "s\n";

        if ($this->cache_path) {
            file_put_contents($this->cache_path, $this->result);
        }

        return $this;
    }

    public function user_agent($user_agent)
    {
        curl_setopt($this->ch, CURLOPT_USERAGENT, $user_agent);
    }

    public function header($key, $val)
    {
        $this->headers[$key] = $val;
    }

    public function generate_headers()
    {
        $headers = [];

        foreach ($this->headers as $key => $val) {
            $headers[] = $key . ': ' . $val;
        }

        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
    }

    public function get()
    {
        return $this->result;
    }

    public function get_json()
    {
        $json = json_decode($this->result, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return false;
    }

    public function get_response_code()
    {
        return curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    }
}
