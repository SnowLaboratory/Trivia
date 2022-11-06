<?php

namespace App\Consumers\TriviaCrack;

use App\Consumers\Consumer;
use Illuminate\Support\Facades\Http;

class TriviaCrackConsumer extends Consumer
{

    use UseTriviaCrackApi;

    public function existingGames() {
        return $this->withoutCache(function () {
            $this
            ->withAuth(['stuff' => 'asdf'])->get("/api/users/{$this->auth()->id}/dashboard");
            return $this
                ->withAuth(['stuff' => 'asdf'])
                ->get("/api/users/{$this->auth()->id}/dashboard");
        });
    }

    public function makeGame() {
        return $this->withCache(function () {
            return $this
                ->withAuth()
                ->withTransformers([
                    QuestionTransformer::class,
                    GameTransformer::class,
                ])
                ->post("/api/users/{$this->auth()->id}/games", [
                    'language' => config('trivia.language')
                ]);
        });
    }

    public function answerQuestion($options) {
        return $this->withCache(function () use($options) {
            return $this
                ->withAuth()
                ->post("/api/users/{$this->auth()->id}/games/{$options['game_id']}/answers", [
                    'type' => $options['type'],
                    'answers' => [
                        $options['answer']
                    ],
                ]);
        });
    }

    // ->answerQuestion(['type' => 'NORMAL', 'answer' => ['id' => 93471857, 'answer' => 0, 'category' => 'ARTS']])

}
