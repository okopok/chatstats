<?php


namespace ChatStats;


class DateHelper
{
    protected static $map = [
        'Понедельник',
        'Вторник',
        'Среда',
        'Четверг',
        'Пятница',
        'Суббота',
        'Воскресение'
    ];

    public static function getDayName(int $weekDayNum): string
    {
        return self::$map[$weekDayNum - 1];
    }
}
