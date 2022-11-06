<?php

namespace App\Consumers\TriviaCrack;

trait UseTriviaCrackApi {

    protected function baseUrl(): string
    {
        return config('trivia.base_url');
    }

    public function auth() {
        return (object) $this
            ->persistState(['headers'])
            ->withoutTransformers(function () {
                return $this->withCache(function ($consumer) {
                    return $consumer->post('/api/login', [
                        'email' => config('trivia.username'),
                        'password' => config('trivia.password'),
                        'language' => config('trivia.language'),
                    ]);
                }, now()->addMonth());
            });
    }

    protected function withAuth(array $headers=[]) {
        return tap($this, function () use ($headers) {
            $this->withHeaders(array_merge_recursive([
                'Cookie' => "ap_session={$this->auth()->session['session']}"
            ], $headers));
        });
    }
}
