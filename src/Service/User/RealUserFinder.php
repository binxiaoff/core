<?php

declare(strict_types=1);

namespace Unilend\Service\User;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Clients;

class RealUserFinder
{
    /** @var ManagerRegistry */
    private $security;
    /** @var ManagerRegistry */
    private $managerRegistry;

    /**
     * @param Security        $security
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(Security $security, ManagerRegistry $managerRegistry)
    {
        $this->security        = $security;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return Clients
     */
    public function __invoke(): object
    {
        $token = $this->security->getToken();
        if ($token instanceof SwitchUserToken) {
            $user          = $token->getOriginalToken()->getUser();
            $entityManager = $this->managerRegistry->getManagerForClass(get_class($user));

            return $entityManager->merge($user);
        }

        return $this->security->getUser();
    }
}
