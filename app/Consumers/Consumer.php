<?php

namespace App\Consumers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;

class Consumer
{
    protected $baseUrl;
    private $useCache;
    private $ttl;
    private $state;
    private $persistentKeys;

    public function __construct()
    {
        $this->useCache = true;
        $this->state = [];
        $this->state['transformers'] = [];
        $this->persistentKeys = [];
    }

    public static function make(...$args) {
        return new static(...$args);
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
            return $this->runTransformers($json);
        }

        $request = app(PendingRequest::class);

        $response = call_user_func($callback, $request);

        $json = $response->json() ?? [];
        cache()->store('redis')->put($key, $json, $this->ttl);

        return $this->runTransformers($json);
    }

    private function runTransformers($json) {
        foreach($this->state['transformers'] ?? [] as $transformer) {
            $json = forward_static_call([$transformer, 'transform'], $json);
        }
        return $json;
    }

    public function withHeaders(array $headers) {
        return tap($this, function () use ($headers) {
            $this->state['headers'] = $headers;
        });
    }

    public function persistState(array $keys) {
        return tap($this, function () use ($keys) {
            $this->persistentKeys = $keys;
        });
    }

    private function useState($key, callable $callback) {
        return tap($this->cache($key, $callback), function () {
            $this->state = Arr::only($this->state, $this->persistentKeys);
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

    public function withTransformers(array $transformers) {
        $result = tap($this, function () use ($transformers) {
            $this->state['transformers'] = $transformers;
        });
        return $result;
    }

    public function withTransformer($transformer) {
        return tap($this, function () use ($transformer) {
            $this->state['transformers'][] = $transformer;
        });
    }

    public function withoutTransformers(callable $callback) {
        $oldTransformers = $this->state['transformers'] ?? [];
        $this->state['transformers'] = [];
        $result = call_user_func($callback, $this);
        $this->state['transformers'] = $oldTransformers;

        return $result;
    }
}
