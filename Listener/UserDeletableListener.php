<?php

namespace Unifik\SystemBundle\Listener;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unifik\SystemBundle\Entity\User;
use Unifik\SystemBundle\Lib\BaseDeletableListener;
use Symfony\Component\Security\Core\SecurityContextInterface;

class UserDeletableListener extends BaseDeletableListener
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * Constructor
     *
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Get the current User from the Security Context
     *
     * @return User
     */
    protected function getCurrentUser()
    {
        return $this->tokenStorage->getToken()->getUser();
    }

    /**
     * @inheritedDoc
     */
    public function isDeletable($entity)
    {
        if ($this->getCurrentUser()->getId() == $entity->getId()) {
            $this->addError('You can\'t delete yourself.');
        }

        return $this->validate();
    }
}
