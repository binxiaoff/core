<?php

declare(strict_types=1);

namespace Unilend\Extension\Project;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Clients, Project, ProjectStatus};

class ListExtension implements QueryCollectionExtensionInterface
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @param Security $security
     */
    public function __construct(
        Security $security
    ) {
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (Project::class !== $resourceClass) {
            return;
        }

        /** @var Clients $user */
        $user      = $this->security->getUser();
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->innerJoin($rootAlias . '.currentStatus', 'cpsh')
            ->where($rootAlias . '.submitterCompany = :company')
            ->leftJoin($rootAlias . '.projectParticipations', 'pp')
            ->leftJoin('pp.projectParticipationContacts', 'pc')
            ->orWhere('cpsh.status IN (:activeStatus) AND pc.client = :client')
            ->setParameters([
                'company'      => $user->getCompany(),
                'activeStatus' => ProjectStatus::DISPLAYABLE_STATUS,
                'client'       => $user,
            ])
        ;
    }
}
