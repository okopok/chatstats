<?php

namespace ChatStats\Parsers;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use function collect;
use function file_exists;
use function file_get_contents;
use function file_put_contents;

class CachedParser
{
    protected ExportParser $parser;

    protected bool $cache = true;

    protected bool $rebuild = false;

    protected string $cacheFile;

    /**
     * CachedParser constructor.
     * @param ExportParser $parser
     * @param bool $cache
     * @param bool $rebuild
     */
    public function __construct(ExportParser $parser, $cacheFile, bool $cache = true, bool $rebuild = false)
    {
        $this->parser = $parser;
        $this->cache = $cache;
        $this->rebuild = $rebuild;
        $this->cacheFile = $cacheFile;
    }

    public function getMessages()
    {
        if ($this->cache) {
            $serializer = SerializerBuilder::create()->build();

            if ($this->rebuild || !file_exists($this->cacheFile)) {
                $messages = $this->parser->getMessages();
                $data = $serializer->serialize(
                    $messages->all(),
                    'json',
                    SerializationContext::create()->setInitialType('array<ChatStats\Entity\Message>')
                );

                file_put_contents($this->cacheFile, $data);
                unset($data);
            } else {
                $messages = $serializer->deserialize(
                    file_get_contents($this->cacheFile),
                    'array<ChatStats\Entity\Message>',
                    'json'
                );
                $messages = collect($messages);
            }
        } else {
            $messages = $this->parser->getMessages();
        }
        return $messages;
    }
}
