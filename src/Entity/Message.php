<?php

namespace ChatStats\Entity;

class Message implements MessageType
{
    public int $message_id = 0;
    public int $date = 0;
    public string $text = '';
    public ?int $reply_to_id = 0;
    public ?Message $reply_to_message = null;
    public ?User $from = null;
    public ?Audio $audio = null;
    public ?Document $document = null;
    public ?Animation $animation = null;
    public ?Photo $photo = null;
    public ?Sticker $sticker = null;
    public ?Video $video = null;
    public ?Voice $voice = null;
    public ?Contact $contact = null;
    public ?Location $location = null;
    public ?Poll $poll = null;
}
