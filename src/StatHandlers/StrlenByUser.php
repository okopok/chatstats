<?php

namespace ChatStats\StatHandlers;

use ChatStats\Entity\Message;
use Tightenco\Collect\Support\Enumerable;
use function mb_strlen;

class StrlenByUser extends AbstractHandler
{

    public function getKey(): string
    {
        return 'strlenByUser';
    }

    public function getDescription(): string
    {
        return 'Общее количество знаков по пользователю';
    }

    protected function exec(): array
    {
        return $this->messages->groupBy(function (Message $item) {
            return $item->from->username;
        })->map(function (Enumerable $messages) {
            return $messages->sum(function (Message $message) {
                return mb_strlen($message->text);
            });
        })->sortDesc()->all();
    }
}
