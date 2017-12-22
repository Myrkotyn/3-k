<?php

namespace App\Event;

use App\Entity\News;
use App\Entity\User;
use Symfony\Component\EventDispatcher\Event;

class NewNewsEvent extends Event
{
    const NAME = AppEvents::NEW_NEWS_EVENT;

    private $user;
    private $news;

    public function __construct(News $news, User $user)
    {
        $this->news = $news;
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return News
     */
    public function getNews(): News
    {
        return $this->news;
    }
}