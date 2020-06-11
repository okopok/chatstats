<?php


namespace ChatStats\StatHandlers;


use ChatStats\Entity\MessageType;
use ChatStats\Entity\Message;

class FirstMessage extends AbstractHandler
{
    public function getKey(): string
    {
        return 'firstMessage';
    }

    public function getDescription(): string
    {
        return 'Самое первое сообщение';
    }

    protected function exec(): array
    {
        /** @var Message $message */
        $message = $this->messages->sortBy('date')->first();
        return [
            'message' => $message->text,
            'datetime' => $message->date,
            'from' => $message->from->username
        ];
    }
}
