<?php

namespace App\Security;

use App\Entity\News;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class NewsVoter extends Voter
{
    const EDIT   = 'edit';
    const DELETE = 'delete';

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::EDIT, self::DELETE])) {
            return false;
        }
        if (!$subject instanceof News) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var News $news */
        $news = $subject;

        switch ($attribute) {
            case self::DELETE:
                return $this->catDelete($news, $user);
            case self::EDIT:
                return $this->canEdit($news, $user);
        }
    }

    private function canEdit(News $news, User $user)
    {
        return $user === $news->getCreatedBy();
    }

    private function catDelete(News $news, User $user)
    {
        if ($this->canEdit($news, $user)) {
            return true;
        }
    }
}