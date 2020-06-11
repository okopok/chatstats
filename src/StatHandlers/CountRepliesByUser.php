<?php

namespace ChatStats\StatHandlers;

use ChatStats\Entity\MessageType;
use ChatStats\Entity\Message;
use function arsort;

class CountRepliesByUser extends AbstractHandler
{
    protected array $users = [];

    public function getKey(): string
    {
        return 'countRepliesByUser';
    }

    public function getDescription(): string
    {
        return 'Общее количество реплаев по пользователю';
    }

    protected function exec(): array
    {
        $this->messages->filter(function (Message $message) {
            return (bool)$message->reply_to_message;
        })->each(function (Message $message) {
            if (empty($this->users[$message->from->username])) {
                $this->users[$message->from->username] = [$message->reply_to_message->from->username => 0];
            }
            if (empty($this->users[$message->from->username][$message->reply_to_message->from->username])) {
                $this->users[$message->from->username][$message->reply_to_message->from->username] = 0;
            }
            $this->users[$message->from->username][$message->reply_to_message->from->username]++;
        });
        return collect($this->users)
            ->mapWithKeys(static function($value, $key) {
                arsort($value);
                return [$key => $value];
            })
            ->sortKeys()->all();
    }
}
