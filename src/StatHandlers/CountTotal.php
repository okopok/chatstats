<?php

namespace ChatStats\StatHandlers;

class CountTotal extends AbstractHandler
{
    public function getKey(): string
    {
        return 'countTotal';
    }

    public function getDescription(): string
    {
        return 'Общее количество сообщений';
    }

    protected function exec(): array
    {
        return [
            'count' => $this->messages->count()
        ];
    }
}
