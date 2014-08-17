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

    /**
     * Activity::__construct()
     * @access public
     */
    public function __construct($handle)
    {
        $this->handle = $handle;
        $this->db = new DB();
    }

    /**
     * Activity::get()
     * @access public
     */
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

    /**
     * Activity::save()
     * @access public
     */
    public function save()
    {
        $data = [
            'handle' => $this->handle,
            'crawled_at' => 'CURRENT_TIMESTAMP',
            'premable' => $this->premable,
            'description' => $this->description,
            // 'security' => $this->security,
            // 'tips' => $this->tips,
            'participants_min' => $this->group_size_min,
            'participants_max' => $this->group_size_max,
            'age_min' => $this->age_min,
            'age_max' => $this->age_max,
            'raw' => $this->_raw
        ];

        if ($this->db->val("SELECT id FROM activities WHERE handle = '%s'", $this->handle)) {
            $this->db->update("UPDATE activities SET %s WHERE handle = '%s'", $data, $this->handle);

        } else {

            $data['created_at'] = 'CURRENT_TIMESTAMP';
            $activity_id = $this->db->insert('activities', $data);

            $this->db->insert('activities_names', [
                'activity_id' => $activity_id,
                'name' => $this->name
            ]);
        }
    }

    /**
     * Activity::crawl()
     * @access public
     */
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

    /**
     * Activity::name()
     * @access private
     */
    private function name()
    {
        $m = null;
        preg_match('/entry-title">(.*?)<\/h1>/', $this->_raw, $m);
        $this->name = trim($m[1]);
    }

    /**
     * Activity::premable()
     * @access private
     */
    private function premable()
    {
        $m = null;
        preg_match('/class="content-preamble">(.*?)<\/span>(.*?)<h2>/ims', $this->_raw, $m);

        $this->premable = trim($m[2]);
    }

    /**
     * Activity::age()
     * @access private
     */
    private function age()
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

    /**
     * Activity::group_size()
     * @access private
     */
    private function group_size()
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

    /**
     * Activity::description()
     * @access private
     */
    private function description()
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
