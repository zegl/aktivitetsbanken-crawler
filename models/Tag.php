<?php

require_once 'ScoutAPI/Category.php';

class Tag extends Common
{
    private $name;
    private $handle;
    private $id;

    private $ScoutAPI;

    public function __construct($handle, $name)
    {
        $this->name = $name;
        $this->handle = $handle;

        $this->ScoutAPI = new \ScoutAPI\Category();

        parent::__construct();
    }

    public function save()
    {
        $this->id = $this->db->insert('categories', ['name' => $this->name, 'handle' => $this->handle]);
        $this->ScoutAPI->save([
            'name' => $this->name,
            'group' => $this->handle
        ]);
    }

    public function crawl($page = 1)
    {
        $url = "http://www.scouterna.se/aktiviteter-och-lager/aktivitetsbanken/upplagg/" . $this->handle . "/";

        if ($page > 1) {
            $url .= "page/" . $page . "/";
        }

        var_dump($url);

        $http = new HTTP();
        $res = $http->url($url)->run()->get();

        if (strpos($res, '<title>Nothing found for') !== false) {
            echo '--- ENDED AT PAGE ' . $page . "\n";

            return;
        }

        $m = null;
        preg_match_all('/aktivitetsbanken\/aktivitet\/(.*?)\/">(.*?)<\/a><\/strong>/', $res, $m);

        foreach ($m[1] as $handle) {
            $activity_id = $this->db->val("SELECT id FROM activities WHERE handle = '%s'", $handle);

            if ($activity_id) {
                $a = $this->db->insert('activities_categories', [
                    'activity_id' => $activity_id,
                    'category_id' => $this->id
                ]);
            }
        }

        $this->crawl(++$page);
    }
}
