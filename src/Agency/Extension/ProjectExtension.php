<?php

declare(strict_types=1);

namespace Unilend\Agency\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;

class ProjectExtension implements QueryCollectionExtensionInterface
{
    private const PREFIX = 'ProjectExtension';

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
    ): void {
        if (false === (Project::class === $resourceClass)) {
            return;
        }

        $user = $this->security->getUser();

        if (false === ($user instanceof User)) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $userParameterName            = static::prefix('user');
        $publishedStatusParameterName = static::prefix('publishedStatus');

        // Borrower condition
        $rootAlias           = $queryBuilder->getRootAliases()[0];
        $borrowerAlias       = static::prefix('borrower');
        $borrowerMemberAlias = static::prefix('borrowerMember');

        $queryBuilder
            ->distinct()
            ->leftJoin("{$rootAlias}.borrowers", $borrowerAlias)
            ->leftJoin("{$borrowerAlias}.members", $borrowerMemberAlias)
            ->orWhere($queryBuilder->expr()->andX(
                "{$borrowerMemberAlias}.user = :{$userParameterName}",
                "{$rootAlias}.currentStatus IN (:{$publishedStatusParameterName})"
            ))
            ->setParameter($userParameterName, $user)
            ->setParameter($publishedStatusParameterName, [Project::STATUS_PUBLISHED, Project::STATUS_ARCHIVED])
        ;

        $token = $this->security->getToken();

        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        if (false === ($staff instanceof Staff)) {
            return;
        }

        $managedUserParameterName = static::prefix('managedUsers');
        $companyParameterName     = static::prefix('company');

        // Participant condition
        $participationAlias       = static::prefix('participation');
        $participationPoolAlias   = static::prefix('participationPool');
        $participationMemberAlias = static::prefix('participationMember');

        $queryBuilder
            ->leftJoin("{$rootAlias}.participationPools", $participationPoolAlias)
            ->leftJoin("{$participationPoolAlias}.participations", $participationAlias)
            ->leftJoin("{$participationAlias}.members", $participationMemberAlias)
            ->orWhere(
                $queryBuilder->expr()->andX(
                    "{$participationMemberAlias}.user IN (:{$managedUserParameterName})",
                    "{$participationAlias}.participant = :{$companyParameterName}",
                    "{$rootAlias}.currentStatus IN (:{$publishedStatusParameterName})"
                )
            )
        ;

        // Agent condition
        $agentAlias       = static::prefix('agent');
        $agentMemberAlias = static::prefix('agentMember');

        $queryBuilder
            ->leftJoin("{$rootAlias}.agent", $agentAlias)
            ->leftJoin("{$agentAlias}.members", $agentMemberAlias)
            ->orWhere(
                $queryBuilder->expr()->andX(
                    "{$agentMemberAlias}.user IN (:{$managedUserParameterName})",
                    "{$agentAlias}.company = :{$companyParameterName}",
                )
            )
        ;

        $queryBuilder
            ->setParameter($managedUserParameterName, iterator_to_array($staff->getManagedUsers(), false))
            ->setParameter($companyParameterName, $staff->getCompany())
        ;
    }

    private static function prefix(string $name): string
    {
        return static::PREFIX . '_' . $name;
    }
}
