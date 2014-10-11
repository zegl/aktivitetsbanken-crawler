<?php

require_once 'ScoutAPI/Activity.php';
require_once 'ScoutAPI/Reference.php';
require_once 'ScoutAPI/Media_file.php';
require_once 'ScoutAPI/Category.php';

class Activity extends Common
{
    private $handle;
    public $name;
    private $premable;
    private $age_min;
    private $age_max;
    private $group_size_min;
    private $group_size_max;
    private $description;
    private $markdown;
    private $author;
    private $attachments = [];
    private $descr = [];

    private $_raw;

    /**
     * Activity::__construct()
     * @access public
     */
    public function __construct($handle = false)
    {
        parent::__construct();
        $this->handle = $handle;
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
    public function save($scoutapi_upload = false)
    {
        $data = [
            'handle' => $this->handle,
            'crawled_at' => 'CURRENT_TIMESTAMP',
            'premable' => $this->premable,
            'description' => $this->markdown,
            'participants_min' => $this->group_size_min,
            'participants_max' => $this->group_size_max,
            'age_min' => $this->age_min,
            'age_max' => $this->age_max
        ];

        file_put_contents('md/' . $this->handle . '.md', $this->markdown);

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

        $json = $data;
        $json['descr_introduction'] = $this->premable;
        $json['name'] = $this->name;

        foreach ($this->descr as $k => $v) {
            $json[$k] = $v;
        }

        $json['categories'] = [];
        $json['references'] = [];
        $json['media_files'] = [];

        $categories = $this->db->rows("SELECT * FROM activities_categories ac JOIN categories c ON c.id = ac.category_id WHERE ac.activity_id = %s", $activity_id);
        
        $scout_category = new \ScoutAPI\Category();

        foreach ($categories as $category) {
            $scout_category_id = $scout_category->exists($category['name']);

            if ($scout_category_id) {
                $json['categories'][] = $scout_category_id;
            } else {
                var_dump($category);
            }
        }

        if (!$scoutapi_upload) {
            return true;
        }

        // Categorize attachments into references and media files
        $references = [
            "text/html" => true
        ];

        foreach ($this->attachments as $attachment) {

            if (isset($references[$attachment["mime_type"]])) {
                $att = new \ScoutAPI\Reference();
                $att_data = [
                    "uri" => $attachment["original_url"]
                ];

            } else {
                $att = new \ScoutAPI\Media_file();
                $att_data = [
                    "mime_type" => $attachment["mime_type"],
                    "uri" => $attachment["original_url"]
                ];
            }

            $id = $att->exists($att_data["uri"]);

            if ($id === false) {
                $id = $att->save($att_data);
            }

            if (isset($references[$attachment["mime_type"]])) {
                $json['references'][] = $id;
            } else {
                $json['media_files'][] = $id;
            }
        }
        
        $scout_activity = new \ScoutAPI\Activity();
        $res = $scout_activity->save($json);

        return true;
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
        $this->name = str_replace('”', '"', $this->name);
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
        $res = str_replace(['<br />','<br>'], "\n", $res);
        $res = trim($res);

        // Convert html entities to utf8
        $res = html_entity_decode($res);

        $m = null;

        // <h2>Swag</h2>
        // <h3>Swag</h3>
        // ”’Swag”’
        preg_match_all('/(<h(2|3)>|”’)(.*?)(<\/h(2|3)>|”’)/', $res, $m);

        $last = 'Så genomför du aktiviteten';
        foreach ($m[0] as $k => $v) {
            $res = explode($v, $res);
            $parts[$last] = $res[0];
            $last = $m[3][$k];
            $parts[$m[3][$k]] = $res = $res[1];
        }

        if (empty($parts)) {
            $parts[$last] = $res;
        }

        foreach ($parts as $k => $v) {

            $this->find_attachments($v);

            // Remove inlince css
            if (strpos($v, "<style type='text/css'>") !== false) {
                $m = null;
                preg_match_all("/<style type='text\/css'>(.*?)<\/style>/sim", $v, $m);
                foreach ($m[0] as $vv) {
                    $v = str_replace($vv, '', $v);
                }
            }

            // Inline lists
            $m = null;
            preg_match_all("/<li>(.*?)<\/li>/sim", $v, $m);
            foreach ($m[0] as $kk => $vv) {
                $list = trim($m[1][$kk]);
                $list = trim($list, ',.');
                $list = '* ' . $list;
                $v = str_replace($vv, $list, $v);
            }

            // More lists
            $v = str_replace('•', '*', $v);

            // Odd quotes
            $v = str_replace('”', '"', $v);

            // Remove HTML-tags
            $v = strip_tags($v);

            // Double newlines
            $v = preg_replace('/(?:(?:\r\n|\r|\n)\s*){2}/s', "\n\n", $v);

            // Unicode whitespace
            $v = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $v);
            $v = trim($v);

            unset($parts[$k]);

            // Images in <h3>?
            // Knivbeviset, I'm looking at you!
            $this->find_attachments($k);
            $k = strip_tags($k);
            $k = html_entity_decode($k);
            $k = trim($k, ':');

            if ($v) {
                $parts[$k] = $v;
            }
        }

        $this->description = $parts;

        // Convert to markdown
        $markdown = '';
        foreach ($parts as $header => $content) {

            if (isset(self::$types[$header])) {
                self::$types[$header]++;
            } else {
                self::$types[$header] = 1;
            }

            $markdown .= "# " . $header . "\n";
            $markdown .= $content . "\n\n";
        }

        $this->markdown = trim($markdown);
        $this->split_description($parts);
    }

    public static $types = [];
    private function split_description($parts)
    {
        $p = [
            "Detta material behöver du" => "descr_material",
            "Säkerhet" => "descr_safety",
            "Förberedelse" => "descr_prepare"
        ];

        $res = [];

        foreach ($parts as $key => $content) {

            $header = $key;

            if (!isset($p[$key])) {
                $key = "descr_main";
            } else {
                $key = $p[$key];
            }

            if (!isset($res[$key])) {
                $res[$key] = "";
            }

            if ($res[$key] !== "") {
                $res[$key] .= "# " . $header . "\n";
            }

            $res[$key] .= $content . "\n\n";
        }

        foreach ($res as $k => $v) {
            $res[$k] = trim($v);
        }

        $this->descr = $res;
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

        $file = 'attachments/tmp_' . md5($url);

        $http = new HTTP();
        $data = $http->url($url)->run()->get();
        file_put_contents($file, $data);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file);

        // Files that doesn't exist
        if ($mime === 'inode/x-empty') {
            unlink($file);

            return false;
        }

        $res['mime_type'] = $mime;
        $res['original_url'] = $url;

        $skip = [
            "text/html" => true,
            "text/plain" => true
        ];

        if (isset($skip[$mime])) {
            unlink($file);

            return $res;
        }

        if (isset($ext[$mime])) {
            $res['uri'] = 'attachments/' . md5($url) . '.' . $ext[$mime];
            file_put_contents($res['uri'], $data);
        } else {

            // Unrecognized files
            // var_dump($mime, $url);
        }

        unlink($file);

        return $res;
    }

    /**
    * Activity::find_attachments()
    * @access private
    */
    private function find_attachments($v)
    {
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
    }
}
