<?php

declare(strict_types=1);

namespace Unilend\Extension\ProjectParticipaction;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Clients, ProjectParticipation};

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
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (ProjectParticipation::class !== $resourceClass) {
            return;
        }
        /** @var Clients $user */
        $user = $this->security->getUser();

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->leftJoin("{$rootAlias}.projectParticipationContacts", 'ppc')
            ->where('ppc.client = :client')
            ->setParameter('client', $user)
        ;
    }
}
