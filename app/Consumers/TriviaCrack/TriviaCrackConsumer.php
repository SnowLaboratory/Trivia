<?php

namespace App\Consumers\TriviaCrack;

use App\Consumers\Consumer;
use Illuminate\Support\Facades\Http;

class TriviaCrackConsumer extends Consumer
{

    private $user;

    public function __construct()
    {
        parent::__construct();
        $this->user = $this->login();
    }

    protected function baseUrl(): string
    {
        return config('trivia.base_url');
    }

    public function login() {
        return $this->withCache(function ($consumer) {
            return $consumer->post('/api/login', [
                'email' => config('trivia.username'),
                'password' => config('trivia.password'),
                'language' => config('trivia.language'),
            ]);
        }, now()->addMonth());
    }

    protected function withLogin(array $headers=[]) {
        return tap($this, function () use ($headers) {
            $this->withHeaders(array_merge_recursive([
                'Cookie' => "ap_session={$this->user->session['session']}"
            ], $headers));
        });
    }

    public function existingGames() {
        return $this->withoutCache(function () {
            return $this
                ->withLogin()
                ->get("/api/users/{$this->user->id}/dashboard");
        });
    }

    public function makeGame() {
        return $this->withCache(function () {
            return $this
                ->withLogin()
                ->post("/api/users/{$this->user->id}/games", [
                    'language' => config('trivia.language')
                ]);
        });
    }

}
