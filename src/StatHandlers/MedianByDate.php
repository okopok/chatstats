<?php

namespace ChatStats\StatHandlers;

use ChatStats\Entity\MessageType;
use ChatStats\Entity\Message;
use function collect;
use function date;

class MedianByDate extends AbstractHandler
{
    public function getKey(): string
    {
        return 'medianByDate';
    }

    public function getDescription(): string
    {
        return 'Среднее количество сообщений';
    }

    protected function exec(): array
    {
        return collect([
            'year' => $this->messages->countBy(static function (Message $item) {
                return date('Y', $item->date);
            })->all(),
            'months' => $this->messages->countBy(static function (Message $item) {
                return date('Y-m', $item->date);
            })->all(),
            'days' =>
                $this->messages->countBy(static function (Message $item) {
                    return date('Y-m-d', $item->date);
                })->all(),
            'weeks' => $this->messages->countBy(static function (Message $item) {
                return date('Y-m N', $item->date);
            })->all(),
            'hours' => $this->messages->countBy(static function (Message $item) {
                return date('Y-m-d H', $item->date);
            })->all(),
        ])->map(static function ($item) {
            $items = collect($item);
            return round($items->sum() / $items->count(), 2);
        })->collect()->all();
    }
}
