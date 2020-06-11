<?php

namespace ChatStats\StatHandlers;

use ChatStats\Entity\Message;

class CountTotalByUser extends AbstractHandler
{

    public function getKey(): string
    {
        return 'countTotalByUser';
    }

    public function getDescription(): string
    {
        return 'Общее количество сообщений по пользователю';
    }

    protected function exec(): array
    {
        return $this->messages->countBy(function (Message $item) {
            return $item->from->username;
        })->sortDesc()->all();
    }
}
