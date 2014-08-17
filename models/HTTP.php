<?php

class HTTP
{

    private $ch;
    private $result;

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
        curl_setopt($this->ch, CURLOPT_URL, $url);

        return $this;
    }

    public function post($data)
    {
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));

        return $this;
    }

    public function run()
    {
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        $this->result = curl_exec($this->ch);

        return $this;
    }

    public function user_agent($user_agent)
    {
        curl_setopt($this->ch, CURLOPT_USERAGENT, $user_agent);
    }

    public function get()
    {
        return $this->result;
    }

    public function get_json()
    {
        $json = json_decode($this->result, true);

        if (json_last_error() == JSON_ERROR_NONE) {
            return $json;
        }

        return false;
    }

    public function get_response_code()
    {
        return curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    }
}
