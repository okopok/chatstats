<?php

namespace ChatStats\StatHandlers;

use ChatStats\DateHelper;
use ChatStats\Entity\MessageType;
use ChatStats\Entity\Message;
use function date;

class CountTotalByDate extends AbstractHandler
{
    public function getKey(): string
    {
        return 'countTotalByDate';
    }

    public function getDescription(): string
    {
        return 'Общее количество сообщений по датам';
    }

    protected function exec(): array
    {

        return [
            'year' => $this->messages->countBy(static function (Message $item) {
                return date('Y', $item->date);
            })->sortKeys()->all(),
            'weeks' => $this->messages->countBy(static function (Message $item) {
                return date('N', $item->date);
            })->sortKeys()->collect()->mapWithKeys(function ($value, $key) {
                return [DateHelper::getDayName($key) => $value];
            })->all(),
            'all_days' =>
                $this->messages->countBy(static function (Message $item) {
                    return date('d', $item->date);
                })->sortKeys()->all(),
            'all_months' =>
                $this->messages->countBy(static function (Message $item) {
                    return date('m', $item->date);
                })->sortKeys()->collect()->mapWithKeys(function ($value, $key) {
                    switch ($key) {
                        case '01':
                            $key = 'Январь';
                            break;
                        case '02':
                            $key = 'Февраль';
                            break;
                        case '03':
                            $key = 'Март';
                            break;
                        case '04':
                            $key = 'Апрель';
                            break;
                        case '05':
                            $key = 'Май';
                            break;
                        case '06':
                            $key = 'Июнь';
                            break;
                        case '07':
                            $key = 'Июль';
                            break;
                        case '08':
                            $key = 'Август';
                            break;
                        case '09':
                            $key = 'Сентябрь';
                            break;
                        case '10':
                            $key = 'Октябрь';
                            break;
                        case '11':
                            $key = 'Ноябрь';
                            break;
                        case '12':
                            $key = 'Декабрь';
                            break;
                    }
                    return [$key => $value];
                })->all(),
            'monthsTop10' => $this->messages->countBy(static function (Message $item) {
                return date('Y-m', $item->date);
            })->sortDesc()->take(10)->all(),
            'daysTop10' =>
                $this->messages->countBy(static function (Message $item) {
                    return date('Y-m-d', $item->date);
                })->sortDesc()->take(10)->all(),
        ];
    }
}
