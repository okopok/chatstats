<?php

namespace ChatStats\StatHandlers;


use ChatStats\Entity\Message;
use ChatStats\Entity\MessageType;
use Tightenco\Collect\Support\Enumerable;

class CountByTypeAndUser extends AbstractHandler
{
    protected array $users = [];

    public function getKey(): string
    {
        return 'countByTypeAndUser';
    }

    public function getDescription(): string
    {
        return 'Сообщения по типам сообщения и пользователям';
    }

    protected function exec(): array
    {
        return $this->messages->filter(function (Message $item) {
            return (bool)$this->getType($item);
        })
            ->groupBy(function (Message $item) {
                return $this->getType($item);
            })->map(static function (Enumerable $messages) {
                return $messages->countBy(function (Message $message) {
                    return $message->from->username;
                })->sortDesc()->all();
            })
            ->all();
    }

    protected function getType(Message $message): string
    {
        $types = ['poll', 'video', 'audio', 'photo', 'sticker', 'voice', 'location', 'animation', 'document'];
        foreach ($types as $type) {
            if ($message->{$type} instanceof MessageType) {
                return $type;
            }
        }
        return "message";
    }
}
