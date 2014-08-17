<?php

require_once 'Tag.php';

class Tags extends Common
{
    public function tags()
    {
        $db = new DB();
        $db->truncate('categories');
        $db->truncate('activities_categories');

        $http = new HTTP();
        $content = $http->url('http://www.scouterna.se/aktiviteter-och-lager/aktivitetsbanken/upplagg/')->run()->get();
        $m = null;
        preg_match_all('/<h2>(.*?)<\/h2>(.*?)aktivitetsbanken\/upplagg\/(.*?)\/">/s', $content, $m);

        foreach ($m[1] as $k => $tag) {
            var_dump($tag);
            $tag = new Tag($m[3][$k], $tag);
            $tag->save();
            $tag->crawl();
        }
    }
}
