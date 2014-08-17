<?php

class Activity
{
	private $handle;
	public $name;
	private $premable;
	private $age_min;
	private $age_max;
	private $group_size_min;
	private $group_size_max;
	private $description;
	private $author;

	private $_raw;

	public function __construct($handle)
	{
		$this->handle = $handle;
		$this->db = new DB();
	}

	public function get()
	{
		$res = [];

		foreach ($this as $k => $v) {
			if (strpos($k, '_') !== 0) {
				$res[$k] = $v;
			}
		}

		return $res;
	}

	public function save()
	{
		$activity_id = $this->db->insert('activities', [
			'handle' => $this->handle,
			'created_at' => 'CURRENT_TIMESTAMP',
			'crawled_at' => 'CURRENT_TIMESTAMP',
			'premable' => $this->premable,
			'description' => $this->description,
			// 'security' => $this->security,
			// 'tips' => $this->tips,
			'participants_min' => $this->group_size_min,
			'participants_max' => $this->group_size_max,
			'age_min' => $this->age_min,
			'age_max' => $this->age_max
		]);

		$this->db->insert('activities_names', [
			'activity_id' => $activity_id,
			'name' => $this->name
		]);
	}

	public function crawl()
	{
		$http = new HTTP();
		$this->_raw = $http->url("http://www.scouterna.se/aktiviteter-och-lager/aktivitetsbanken/aktivitet/" . $this->handle . "/")->run()->get();

		if (!$this->_raw) {
			return false;
		}

		$this->name();
		$this->premable();
		$this->age();
		$this->group_size();
		$this->description();

		return true;
	}

	public function name()
	{
		$m = null;
		preg_match('/entry-title">(.*?)<\/h1>/', $this->_raw, $m);
		$this->name = trim($m[1]);
	}

	public function premable()
	{
		$m = null;
		preg_match('/class="content-preamble">(.*?)<\/span>(.*?)<h2>/ims', $this->_raw, $m);

		$this->premable = trim($m[2]);
	}

	public function age()
	{
		$m = null;
		preg_match('/<p><strong>Åldersgrupper:<\/strong>(.*?)<\/p>/', $this->_raw, $m);

		$age = trim($m[1]);
		$m = null;
		preg_match_all("/([0-9]+)/", $age, $m);

		$this->age_min = 99;
		$this->age_max = 0;

		foreach ($m[0] as $v) {
			$v = (int) $v;

			if ($this->age_min > $v) {
				$this->age_min = $v;
			}

			if ($this->age_max < $v) {
				$this->age_max = $v;
			}
		}
	}

	public function group_size()
	{
		$m = null;
		preg_match('/<p><strong>Gruppstorlek:<\/strong>(.*?)<\/p>/', $this->_raw, $m);

		$size = trim($m[1]);

		$m = null;
		preg_match_all("/([0-9]+)/", $size, $m);

		$this->group_size_min = 99;
		$this->group_size_max = 0;

		foreach ($m[0] as $v) {
			$v = (int) $v;

			if ($this->group_size_min > $v) {
				$this->group_size_min = $v;
			}

			if ($this->group_size_max < $v) {
				$this->group_size_max = $v;
			}
		}
	}

	public function description()
	{
		$m = null;
		preg_match('/<h2>Så genomför du aktiviteten<\/h2>(.*?)(<h2>(Aktiviteten är gjord av|Referenser)<\/h2>|<\/div><!-- .entry-content -->)/sim', $this->_raw, $m);

		if (!isset($m[1])) {
			var_dump($m);
			die();
			return;
		}

		$this->description = trim($m[1]);
	}
}