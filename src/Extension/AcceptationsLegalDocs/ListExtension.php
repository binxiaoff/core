<?php

declare(strict_types=1);

namespace Unilend\Extension\AcceptationsLegalDocs;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{AcceptationsLegalDocs, Clients};

class ListExtension implements QueryCollectionExtensionInterface
{
    /** @var Security */
    private Security $security;

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
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (AcceptationsLegalDocs::class !== $resourceClass || $this->security->isGranted(Clients::ROLE_ADMIN)) {
            return;
        }

        /** @var Clients $user */
        $user  = $this->security->getUser();
        $staff = $user instanceof Clients ? $user->getCurrentStaff() : null;

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere($rootAlias . '.addedBy = :currentStaff')
            ->setParameter('currentStaff', $staff)
            ->orderBy($rootAlias . '.added', 'DESC')
        ;
    }
}
