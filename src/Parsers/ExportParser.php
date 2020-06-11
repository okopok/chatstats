<?php

namespace ChatStats\Parsers;

use ChatStats\UserHelper;
use DateTime;
use DirectoryIterator;
use Str\Str;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\VarDumper\VarDumper;
use Tightenco\Collect\Support\Collection;
use Traversable;
use ChatStats\Entity\Animation;
use ChatStats\Entity\Audio;
use ChatStats\Entity\Photo;
use ChatStats\Entity\Document;
use ChatStats\Entity\Location;
use ChatStats\Entity\Message;
use ChatStats\Entity\Poll;
use ChatStats\Entity\Sticker;
use ChatStats\Entity\User;
use ChatStats\Entity\Video;
use ChatStats\Entity\Voice;
use function collect;
use function dd;
use function file_get_contents;

class ExportParser
{
    protected User $lastUser;
    protected string $dir;

    /**
     * ExportParser constructor.
     * @param $dir
     */
    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    public function getMessages(): Traversable
    {
        $messages = collect([]);
        foreach ($this->getMessagesByStacks() as $stack) {
            if ($stack->isEmpty()) {
                continue;
            }
            /** @var Message $message */
            foreach ($stack as $message) {
                if ($message === null) {
                    continue;
                }
//                dd($message);
                $messages->put($message->message_id, $message);
            }
        }

        $this->normalizeReplies($messages);

        return $messages;
    }

    protected function getMessagesByStacks(): Traversable
    {
        foreach ($this->parseDir() as $crawler) {
            yield $this->getMessageStacks($this->getMessagesList($crawler));
        }
    }

    protected function parseDir(): ?\Generator
    {
        $files = [];
        foreach (new DirectoryIterator($this->dir) as $key => $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) continue;
            if ($fileInfo->getFilename() == '.DS_Store') continue;

            $files[] = $fileInfo->getRealPath();
        }
        natsort($files);
        foreach ($files as $key => $file) {
            VarDumper::dump($key . ' => ' . $file);
            yield new Crawler(file_get_contents($file));
        }
    }

    protected function getMessageStacks(Crawler $messages): Collection
    {
        $ms = $messages->each(function (Crawler $item, $i) {
            if ($this->isServiceMessage($item)) {
                return null;
            }

            if (!$this->isJoined($item)) {
                $this->lastUser = $this->getFrom($item);
            }

            return $this->getMessage($this->lastUser, $item);
        });
        return collect($ms)->filter();
    }

    protected function isServiceMessage(Crawler $message): bool
    {
        return $message->attr('class') === 'message service';
    }

    protected function isJoined(Crawler $message): bool
    {
        return (new Str($message->attr('class')))->contains('joined');
    }

    protected function getFrom(Crawler $message): User
    {
        $fromName = $message->filter('.from_name')->first()->text('unknown');
        $user = new User();
        $user->username = UserHelper::norm($fromName);
        return $user;
    }

    protected function getMessage(User $from, Crawler $message): Message
    {
        $messageEntity = new Message();
        $messageEntity->from = $from;
        $messageEntity->message_id = $this->getMessageId($message);

        $messageEntity->date = (new DateTime($this->getMessageDate($message)))->getTimestamp();
        $messageEntity->text = $this->getText($message);

        if ($this->isReply($message)) {
            $messageEntity->reply_to_id = $this->getReply($message);
        }

        $mediaWrap = $this->getMediaWrap($message);
        if ($this->hasMedia($mediaWrap)) {
            if ($this->isPhoto($mediaWrap)) {
                $messageEntity->photo = new Photo();
            } else if ($this->isSticker($mediaWrap)) {
                $messageEntity->sticker = new Sticker();
            } else if ($this->isVideo($mediaWrap)) {
                $messageEntity->video = new Video();
            } else if ($this->isVoice($mediaWrap)) {
                $messageEntity->voice = new Voice();
            } else if ($this->isLiveLoc($mediaWrap)) {
                $messageEntity->location = new Location();
            } else if ($this->isAudio($mediaWrap)) {
                $messageEntity->audio = new Audio();
            } else if ($this->isPoll($mediaWrap)) {
                $messageEntity->poll = new Poll();
            } else if ($this->isDocument($mediaWrap)) {
                $messageEntity->document = new Document();
            } else if ($this->isAnimation($mediaWrap)) {
                $messageEntity->animation = new Animation();
            }
        }
        return $messageEntity;
    }

    protected function getMessageId(Crawler $message): int
    {
        return (int)(new Str($message->attr('id')))
            ->removeLeft('message')
            ->getString();
    }

    protected function getMessageDate(Crawler $message): ?string
    {
        return $message->filter('.date')->first()->attr('title');
    }

    protected function getText(Crawler $message): string
    {
        return $message->filter('.text')->first()->text('');
    }

    protected function isReply(Crawler $message): bool
    {
        return (bool)$message->filter('div.reply_to > a')->count();
    }

    protected function getReply(Crawler $message): int
    {
        $href = $message->filter('div.reply_to > a')->first()->attr('href');
        $href = new Str($href);
        return (int)$href->pop('#')->trim('#')->removeLeft('go_to_message')->getString();
    }

    protected function getMediaWrap(Crawler $message): Crawler
    {
        return $message->filter('.media_wrap');
    }

    protected function hasMedia(Crawler $wrap): bool
    {
        return (bool)$wrap->count();
    }

    protected function isPhoto(Crawler $wrap): bool
    {
        $title = new Str($wrap->filter('div.media_photo > div.body > div.title')->first()->text(''));
        $isPhoto = (bool)$wrap->filter('div.media_photo')->count();
        return $isPhoto && $title->toLowerCase()->contains('photo');
    }

    protected function isSticker(Crawler $wrap): bool
    {
        $title = new Str($wrap->filter('div.media_photo > div.body > div.title')->first()->text(''));
        $isPhoto = (bool)$wrap->filter('div.media_photo')->count();
        return $isPhoto && $title->toLowerCase()->contains('sticker');
    }

    protected function isVideo(Crawler $wrap): bool
    {
        return (bool)$wrap
            ->filter('.media_video')
            ->count();
    }

    protected function isVoice(Crawler $wrap): bool
    {
        return (bool)$wrap
            ->filter('.media_voice_message')
            ->count();
    }

    protected function isLiveLoc(Crawler $wrap): bool
    {
        return (bool)$wrap->filter('.media_live_location')->count();
    }

    protected function isAudio(Crawler $wrap): bool
    {
        return (bool)$wrap
            ->filter('.media_audio_file')
            ->count();
    }

    protected function isPoll(Crawler $wrap): bool
    {
        return (bool)$wrap->filter('.media_poll')->count();
    }

    protected function isDocument(Crawler $wrap): bool
    {
        return (bool)$wrap
            ->filter('.media_document')
            ->count();
    }

    protected function isAnimation(Crawler $wrap): bool
    {
        return (bool)$wrap
            ->filter('.media_animation')
            ->count();
    }

    protected function getMessagesList(Crawler $crawler): Crawler
    {
        return $crawler->filter('div.message');
    }

    protected function normalizeReplies(Collection $messages): void
    {
        $replies = [];
        /** @var Message $message */
        foreach ($messages as $message) {
            if ($message->reply_to_id) {
                $replies[(string)$message->reply_to_id][] = $message->message_id;
            }
        }
        foreach ($replies as $replyId => $messageIds) {
            foreach ($messageIds as $messageId) {
                $messages[$messageId]->reply_to_message = $messages->get($replyId);
            }
        }
    }
}
