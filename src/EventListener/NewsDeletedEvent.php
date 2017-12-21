<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Event;

class NewsDeletedEvent extends Event
{
        const NAME = 'news.deleted';

        public function eventName()
        {
            return self::NAME;
        }
}