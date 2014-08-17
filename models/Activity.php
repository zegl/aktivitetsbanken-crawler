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
    private $attachments = [];

    private $_raw;

    /**
     * Activity::__construct()
     * @access public
     */
    public function __construct($handle = false)
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
            'description' => json_encode($this->description),
            'participants_min' => $this->group_size_min,
            'participants_max' => $this->group_size_max,
            'age_min' => $this->age_min,
            'age_max' => $this->age_max,
            'raw' => $this->_raw
        ];

        // Create activity
        if ($activity_id = $this->db->val("SELECT id FROM activities WHERE handle = '%s'", $this->handle)) {
            $this->db->update("UPDATE activities SET %s WHERE handle = '%s'", $data, $this->handle);

        } else {
            $data['created_at'] = 'CURRENT_TIMESTAMP';
            $activity_id = $this->db->insert('activities', $data);
        }

        // Save attachments
        foreach ($this->attachments as $att) {
            if ($att_id = $this->db->val("SELECT id FROM attachments WHERE original_url = '%s' AND activity_id = %s", $att['original_url'], $activity_id)) {
                $this->db->update("UPDATE attachments SET %s WHERE id = %s", $att, $att_id);
                continue;
            }

            $att['activity_id'] = $activity_id;
            $this->db->insert('attachments', $att);
        }

        if ($name_id = $this->db->val("SELECT id FROM activities_names WHERE activity_id = %s", $activity_id)) {
            $this->db->update("UPDATE activities_names SET %s WHERE id = %s", ['name' => $this->name], $name_id);
        } else {
            $this->db->insert('activities_names', ['name' => $this->name, 'activity_id' => $activity_id]);
        }
    }

    /**
     * Activity::crawl()
     * @access public
     */
    public function crawl()
    {
        $raw_file = 'raw/' . $this->handle . '.html';

        if (file_exists($raw_file)) {
            $this->_raw = file_get_contents($raw_file);

        } else {
            $http = new HTTP();
            $this->_raw = $http->url("http://www.scouterna.se/aktiviteter-och-lager/aktivitetsbanken/aktivitet/" . $this->handle . "/")->run()->get();

            if (!$this->_raw) {
                return false;
            }
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
        $this->name = html_entity_decode($this->name);
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
        $parts = [];

        $m = null;
        preg_match('/<h2>Så genomför du aktiviteten<\/h2>(.*?)<\/div><!-- .entry-content -->/sim', $this->_raw, $m);

        if (!isset($m[1])) {
            var_dump($m);
            die();

            return;
        }

        $res = $m[1];
        $res = str_replace('<br />', '', $res);
        $res = trim($res);

        $m = null;
        preg_match_all('/<h3>(.*?)<\/h3>/', $res, $m);

        $last = 'Så genomför du aktiviteten';
        foreach ($m[0] as $k => $v) {
            $res = explode($v, $res);
            $parts[$last] = $res[0];
            $last = $m[1][$k];
            $parts[$m[1][$k]] = $res = $res[1];
        }

        foreach ($parts as &$v) {

            // Find attachments
            $m = null;
            preg_match_all('/<a href="(.*?)"(.*?)>(.*?)<\/a>/', $v, $m);

            foreach ($m[1] as $vv) {

                if (strpos($vv, 'http') !== 0) {
                    $vv = 'http://www.scouterna.se/' . ltrim($vv, '/');
                }

                $att = $this->attachment($vv);

                if ($att) {
                    $this->attachments[] = $att;
                }
            }

            $v = strip_tags($v);
            $v = html_entity_decode($v);
            $v = trim($v);
        }

        $this->description = $parts;
    }

    /**
    * Activity::attachment()
    * @access public
    */
    public function attachment($url)
    {
        $res = [];

        $ext = [
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'application/msword' => 'doc',
            'image/jpeg' => 'jpg',

            // Who doesn't love Microsoft Office?
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx'
        ];

        $file = 'attachments/' . md5($url);

        if (!file_exists($file)) {
            file_put_contents($file, @file_get_contents($url));
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file);

        // Files that doesn't exist
        if ($mime === 'inode/x-empty') {
            return false;
        }

        $res['mime_type'] = $mime;
        $res['original_url'] = $url;


        if ($mime === 'text/html') {
            return $res;
        }

        if (isset($ext[$mime])) {
            $res['uri'] = 'attachments/' . md5($url) . '.' . $ext[$mime];
        } else {

            // Unrecognized filesx
            var_dump($mime, $url);
        }

        return $res;
    }
}
