<?php

namespace ChatStats\StatHandlers;

use Tightenco\Collect\Support\Enumerable;

abstract class AbstractHandler
{
    protected array $data = [];

    protected Enumerable $messages;

    abstract public function getDescription(): string;

    public function setMessages(Enumerable $messages): AbstractHandler
    {
        $this->messages = $messages;
        return $this;
    }

    public function getTemplate(): string
    {
        return $this->getKey() . '.twig';
    }

    abstract public function getKey(): string;

    public function handle(): AbstractHandler
    {
        $this->data = $this->exec();
        return $this;
    }

    abstract protected function exec(): array;

    public function getData(): array
    {
        return $this->data;
    }
}
