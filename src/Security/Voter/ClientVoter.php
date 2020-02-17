<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\Clients;

class ClientVoter extends AbstractEntityVoter
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
    protected function isGrantedAll($subject, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted(Clients::ROLE_ADMIN) || $subject->getId() === $user->getId();
    }
}
