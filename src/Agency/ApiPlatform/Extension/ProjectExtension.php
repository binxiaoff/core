<?php

declare(strict_types=1);

namespace Unilend\Agency\ApiPlatform\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Staff;

class ProjectExtension implements QueryCollectionExtensionInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
        if (false === (Project::class === $resourceClass)) {
            return;
        }

        $user = $this->security->getUser();

        $token = $this->security->getToken();

        /** @var Staff $staff */
        $staff = $token && $token->hasAttribute('staff') ? $token->getAttribute('staff') : null;

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->distinct()
            ->leftJoin($rootAlias . '.borrowers', 'b')
            ->leftJoin('b.members', 'bm')
            ->where('bm.user = :user')
            ->setParameter('user', $user)
        ;

        if ($staff) {
            $queryBuilder
                ->leftJoin($rootAlias . '.participations', 'p')
                ->leftJoin('p.members', 'pm')
                ->orWhere('pm.user IN (:managedUsers) and p.participant = :company')
                ->setParameter('managedUsers', iterator_to_array($staff->getManagedUsers(), false))
                ->setParameter('company', $staff->getCompany())
            ;
        }
    }
}
