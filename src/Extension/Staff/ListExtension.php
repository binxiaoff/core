<?php

declare(strict_types=1);

namespace Unilend\Extension\Staff;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\Staff;

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
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        /** @var Clients $user */
        $user = $this->security->getUser();
        if (Staff::class !== $resourceClass || !$user instanceof Clients) {
            return;
        }

        $staff = $user->getStaff();

        if (null === $staff || false === ($staff->hasRole(Staff::DUTY_STAFF_MANAGER) || $staff->hasRole(Staff::DUTY_STAFF_ADMIN))) {
            $queryBuilder->andWhere('FALSE');

            return;
        }

        $company = $user->getCompany();

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere("{$rootAlias}.company = :company")
            ->setParameter('company', $company)
        ;
    }
}
