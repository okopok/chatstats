<?php

namespace ChatStats;

use ChatStats\StatHandlers\AbstractHandler;
use Symfony\Component\Stopwatch\Stopwatch;
use Tightenco\Collect\Support\Collection;
use Tightenco\Collect\Support\Enumerable;
use Twig\Environment;
use function collect;
use function file_put_contents;

class StatMachine
{
    protected Collection $stats;

    protected array $handlers = [];

    protected Enumerable $messages;

    protected Stopwatch $stopwatch;

    protected array $descriptionsByKey = [];

    protected Environment $twig;

    /**
     * StatMachine constructor.
     * @param Enumerable $messages
     * @param Stopwatch $stopwatch
     * @param array $handlers
     * @param Environment $twig
     */
    public function __construct(Enumerable $messages, Stopwatch $stopwatch, array $handlers = [], Environment $twig)
    {
        $this->stats = collect([]);
        $this->messages = $messages;
        $this->addHandlers($handlers);
        $this->stopwatch = $stopwatch;
        $this->twig = $twig;
    }

    public function addHandlers(array $handlers)
    {
        foreach ($handlers as $handler) {
            $this->addHandler($handler);
        }
    }

    public function addHandler(AbstractHandler $handler)
    {
        $this->handlers[$handler->getKey()] = $handler;
        $this->descriptionsByKey[$handler->getKey()] = $handler->getDescription();
    }

    public function calculate(): void
    {
        $this->stopwatch->openSection();
        /** @var  $handler */
        foreach ($this->handlers as $handler) {
            $this->stopwatch->start('handler ' . $handler->getKey());
            $handler->setMessages($this->messages);
            $this->stats[] = $handler->handle();
            $this->stopwatch->stop('handler ' . $handler->getKey());
        }
        $this->stopwatch->stopSection('calc');
    }

    public function render($outputFile, $title)
    {
        $content = $this->twig->render(
            'index.twig',
            ['handlers' => $this->handlers, 'title' => $title]
        );

        file_put_contents(
            $outputFile,
            $content
        );
    }

    public function getStats(): Collection
    {
        return $this->stats;
    }

    public function getDiscriptionByKey(): array
    {
        return $this->descriptionsByKey;
    }
}
