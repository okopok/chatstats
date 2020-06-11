<?php

namespace ChatStats;

use Str\Str;

class UserHelper
{
    public static function norm(string $username): string
    {
        return (string)(new Str($username))
            ->popReversed(' via @')
            ->replace('Саша Майонез', 'Александр Кузнецов')
            ->replace('Таня 🐠 Кондратова', 'Таня Кондратова')
            ->replace('Катя 🌿 Хмелевская', 'Катя Хмелевская')
            ->replace('Серёга', 'Сергей Болдин')
            ->trim();
    }
}
