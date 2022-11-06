<?php

namespace App\Consumers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Consumer
{
    protected $baseUrl;
    private $useCache;
    private $ttl;
    private $state;

    public function __construct()
    {
        $this->useCache = true;
        $this->state = [];
    }

    protected function baseUrl() : string {
        return $this->baseUrl ?? '';
    }

    // protected function cache($method, $url, $data=null) {
    //     $key = "$method:$url:" . sha1(json_encode($data));

    //     if ($this->useCache && $json = cache()->store('redis')->get($key)) {
    //         return (object) $json;
    //     }

    //     $json = Http::$method($url, $data)->json() ?? [];
    //     cache()->store('redis')->put($key, $json, $this->ttl);
    //     return (object) $json;
    // }

    private function makeKey($method, $url, $data=null) {
        return "$method:$url:" . sha1(json_encode($data));
    }

    protected function cache($key, callable $callback) {
        if ($this->useCache && $json = cache()->store('redis')->get($key)) {
            return (object) $json;
        }

        $request = app(PendingRequest::class);

        $response = call_user_func($callback, $request);

        $json = $response->json() ?? [];
        cache()->store('redis')->put($key, $json, $this->ttl);
        return (object) $json;
    }

    public function withHeaders(array $headers) {
        return tap($this, function () use ($headers) {
            $this->state['headers'] = $headers;
        });
    }

    private function useState($key, callable $callback) {
        return tap($this->cache($key, $callback), function () {
            $this->state = [];
        });
    }

    private function state($key, $default=null) {
        return $this->state[$key] ?? $default;
    }

    private function request($method, $url, $data=null) {
        $url = $this->baseUrl().$url;
        return $this->useState(
            $this->makeKey($method, $url, $data),
            function (PendingRequest $request) use($url, $data, $method) {
                if ($this->state('headers')) {
                    $request->withHeaders($this->state('headers'));
                }
                return $request->$method($url, $data);
            }
        );
    }

    public function post($url, $data=null) {
        return $this->request('post', $url, $data);
    }

    public function get($url, $data=null) {
        return $this->request('get', $url, $data);
    }

    public function put($url, $data=null) {
        return $this->request('put', $url, $data);
    }

    public function delete($url, $data=null) {
        return $this->request('delete', $url, $data);
    }

    public function disableCache() {
        return tap($this, function () {
            $this->useCache = false;
        });
    }

    public function enableCache() {
        return tap($this, function () {
            $this->useCache = true;
        });
    }

    public function withCacheMutation (callable $callback, $ttl=null) {
        $oldUseCache = $this->useCache;
        $oldTTL = $this->ttl;
        $this->ttl = $ttl;
        $result = call_user_func($callback, $this);
        $this->useCache = $oldUseCache;
        $this->ttl = $oldTTL;
        return $result;
    }

    public function withoutCache(callable $callback, $ttl=null) {
        return $this->withCacheMutation(function ($consumer) use ($callback, $ttl) {
            $this->disableCache();
            return call_user_func($callback, $consumer, $ttl);
        }, $ttl);
    }

    public function withCache(callable $callback, $ttl=null) {
        return $this->withCacheMutation(function ($consumer) use ($callback, $ttl) {
            $this->enableCache();
            return call_user_func($callback, $consumer, $ttl);
        }, $ttl);
    }
}
