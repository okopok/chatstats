<?php


namespace ChatStats\StatHandlers;


use ChatStats\Entity\MessageType;
use ChatStats\Entity\Message;
use function number_format;

class UserMedianMessageLength extends AbstractHandler
{

    public function getKey(): string
    {
        return 'userMedianMessageLength';
    }

    public function getDescription(): string
    {
        return 'Средняя длинна сообщений по пользователям';
    }

    protected function exec(): array
    {
        $strlenByUser = (new StrlenByUser())->setMessages($this->messages)->handle()->getData();
        $countTotalByUser = (new CountTotalByUser())->setMessages($this->messages)->handle()->getData();
        $result = [];
        foreach ($countTotalByUser as $user => $ct) {
            $result[$user] = number_format($strlenByUser[$user] / $ct, 0);
        }

        return collect($result)->sortDesc()->all();
    }
}
