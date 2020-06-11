<?php

namespace ChatStats\StatHandlers;

use Tightenco\Collect\Support\Collection;
use ChatStats\Entity\MessageType;
use ChatStats\Entity\Message;
use function collect;

class CountTotalUsers extends AbstractHandler
{
    protected Collection $users;

    public function getKey(): string
    {
        return 'countTotalUsers';
    }

    public function getDescription(): string
    {
        return 'Общее количество всех пользователей за всё время';
    }

    protected function exec(): array
    {
        $this->users = collect([]);
        $this->messages->each(function (Message $message) {
            if (!$this->users->has($message->from->username)) {
                $this->users[$message->from->username] = 1;
            }
        });
        return $this->users->keys()->sort()->values()->all();
    }
}
