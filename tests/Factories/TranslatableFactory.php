<?php

namespace Makeable\LaravelTranslatable\Tests\Factories;

use Makeable\LaravelFactory\Factory;

class TranslatableFactory extends Factory
{
    public function definition()
    {
        return [
            'locale' => 'da'
        ];
    }

    public function english()
    {
        return $this->state([
            'locale' => 'en'
        ]);
    }

    public function swedish()
    {
        return $this->state([
            'locale' => 'sv'
        ]);
    }
}