<?php

namespace App\EventListener;

use App\Event\NewNewsEvent;
use Doctrine\ORM\EntityManagerInterface;

class NewNewsListener
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function onAppNewNews(NewNewsEvent $event)
    {
        $news = $event->getNews();
        $user = $event->getUser();
        $news->setCreatedBy($user)
            ->setUpdatedBy($user);
    }
}