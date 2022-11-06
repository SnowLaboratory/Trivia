<?php

namespace App\Consumers\TriviaCrack;

use App\Consumers\Consumer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class QuestionTransformer extends Consumer
{

    use UseTriviaCrackApi;

    public function __construct(
        private array $data,
        private int $gameId,
    ){}

    public static function transform($json) {

        $spins = Arr::get($json, 'spins_data.spins', null);
        if (!$spins) return $json;

        return Arr::refMap($json, 'spins_data.spins',
            fn($spin) => Arr::refMap($spin, 'questions',
                fn($question) => Arr::map($question,
                    fn($chance) => static::make($chance, $json['id'])
        )));
    }

    public function next() {
        return $this->withCache(function () {
            $data = $this->data;
            return $this
                ->withAuth()
                ->withTransformers([
                    QuestionTransformer::class,
                    GameTransformer::class,
                ])
                ->post("/api/users/{$this->auth()->id}/games/{$this->gameId}/answers", [
                    'type' => $data['media_type'],
                    'answers' => [
                        'id' => $data['id'],
                        'answer' => $data['correct_answer'],
                        'category' => $data['category'],
                    ],
                ]);
        });
    }

    public function data() {
        return $this->data;
    }

    // ->answerQuestion(['type' => 'NORMAL', 'answer' => ['id' => 93471857, 'answer' => 0, 'category' => 'ARTS']])

}
