<?php

namespace ChatStats\StatHandlers;

use ChatStats\DateHelper;
use Tightenco\Collect\Support\Enumerable;
use ChatStats\Entity\MessageType;
use ChatStats\Entity\Message;
use function array_key_exists;
use function date;
use function range;

class CountUsersByDayNHours extends AbstractHandler
{
    public function getKey(): string
    {
        return 'countUsersByDayNHours';
    }

    public function getDescription(): string
    {
        return 'Общее количество сообщений с разбивкой по пользователям, часам и дням недели';
    }

    protected function exec(): array
    {
        return $this->messages->groupBy(function (Message $item) {
            return $item->from->username;
        })->map(static function (Enumerable $messages, $username) {
            $gr = $messages->groupBy(function (Message $message) {
                return date('N', $message->date);
            })->sortKeys()->mapWithKeys(function ($week, $weekNum) {
                return [DateHelper::getDayName($weekNum) => $week];
            });
            return [
                'hash' => md5($username),
                'total' => $messages->count(),
                'dayOfWeek' => $gr->map(
                    function (Enumerable $messages) {
                        return $messages->count();
                    })->all(),
                'dayOfWeekByHour' => $gr->map(static function (Enumerable $messages) {
                    return $messages->countBy(static function (Message $message) {
                        return date('G', $message->date);
                    })->all();
                })->map(static function ($day) {
                    foreach (range(0, 23) as $hour) {
                        if (!array_key_exists($hour, $day)) {
                            $day[$hour] = 0;
                        }
                    }
                    ksort($day);
                    return $day;
                })->all()
            ];
        })->sortByDesc('total')->all();
    }
}
