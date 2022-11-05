<?php

namespace App\Consumers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Consumer
{
    protected function cache($method, $url, $data=null) {
        $key = "$method:$url:" . sha1(json_encode($data));
        if ($json = cache()->store('redis')->get($key)) return (object) $json;

        $json = Http::$method($url, $data)->json() ?? [];
        cache()->store('redis')->put($key, $json);
        return (object) $json;
    }

    public function post($url, $data=null) {
        return $this->cache('post', $url, $data);
    }

    public function get($url, $data=null) {
        return $this->cache('get', $url, $data);
    }

    public function put($url, $data=null) {
        return $this->cache('put', $url, $data);
    }

    public function delete($url, $data=null) {
        return $this->cache('delete', $url, $data);
    }
}
