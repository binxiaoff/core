<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{ClientStatus, Clients};

class ClientStatusVoter extends AbstractVoter
{
    public const ATTRIBUTE_VIEW = 'view';

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
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return $subject instanceof ClientStatus && parent::supports($attribute, $subject);
    }

    /**
     * @param ClientStatus $clientStatus
     * @param Clients      $user
     *
     * @return bool
     */
    protected function canView(ClientStatus $clientStatus, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted(Clients::ROLE_ADMIN) || $user === $clientStatus->getClient();
    }
}
