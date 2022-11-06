<?php

namespace App\Consumers\TriviaCrack;

use App\Consumers\Consumer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GameTransformer extends Consumer
{

    use UseTriviaCrackApi;

    public function __construct(
        private array $data,
    ){}

    public static function transform($json) {
        return static::make($json);
    }

    public function questions() : array {
        return Arr::flatten(Arr::pluck(Arr::get($this->data, 'spins_data.spins'), 'questions'));
    }

    public function data () {
        return $this->data;
    }

}
