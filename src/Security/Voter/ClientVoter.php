<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\Clients;

class ClientVoter extends AbstractVoter
{
    public const ATTRIBUTE_VIEW = 'view';
    public const ATTRIBUTE_EDIT = 'edit';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param Clients $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function canView(Clients $subject, Clients $user)
    {
        return $this->authorizationChecker->isGranted(Clients::ROLE_ADMIN) || $subject->getId() === $user->getId();
    }

    /**
     * @param Clients $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function canEdit(Clients $subject, Clients $user)
    {
        return $this->authorizationChecker->isGranted(Clients::ROLE_ADMIN) || $subject->getId() === $user->getId();
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return $subject instanceof Clients && parent::supports($attribute, $subject);
    }
}
