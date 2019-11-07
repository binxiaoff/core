<?php

declare(strict_types=1);

namespace Unilend\Api\Extension\ProjectParticipaction;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\ProjectParticipation;

class ListExtension implements QueryCollectionExtensionInterface
{
    /** @var Security */
    private $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param string|null                 $operationName
     */
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ): void {
        if (ProjectParticipation::class !== $resourceClass) {
            return;
        }
        /** @var Clients $user */
        $user = $this->security->getUser();

        $queryBuilder
            ->leftJoin('o.projectParticipationContacts', 'ppc')
            ->where('o.company = :company')
            ->orWhere('ppc.client = :client')
            ->setParameter('company', $user->getCompany())
            ->setParameter('client', $user)
        ;
    }
}
