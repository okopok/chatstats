<?php

namespace ChatStats\StatHandlers;

use ChatStats\Entity\MessageType;
use ChatStats\Entity\Message;
use function mb_strlen;

class TotalStrlen extends AbstractHandler
{

    public function getKey(): string
    {
        return 'totalStrlen';
    }

    public function getDescription(): string
    {
        return 'Общее количество знаков в чате';
    }

    protected function exec(): array
    {
        return [
            'strlen' =>
                $this->messages->sum(function (Message $message) {
                    return mb_strlen($message->text);
                })];
    }
}
