<?php

declare(strict_types=1);

namespace Unilend\Agency\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Agency\Entity\Participation;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;

class ParticipationExtension implements QueryCollectionExtensionInterface
{
    private const PREFIX = 'ParticipationExtension';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if (false === (Participation::class === $resourceClass)) {
            return;
        }

        $user = $this->security->getUser();

        if (false === ($user instanceof User)) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        // Variable used
        $userParameterName              = static::prefix('user');
        $borrowerSecondaryParameterName = static::prefix('secondary');
        $publishedStatusParameterName   = static::prefix('publishedStatus');

        $queryBuilder
            ->setParameter($userParameterName, $user)
            ->setParameter($borrowerSecondaryParameterName, false)
            ->setParameter($publishedStatusParameterName, [Project::STATUS_PUBLISHED, Project::STATUS_ARCHIVED])
        ;

        // Borrower condition
        $rootAlias                      = $queryBuilder->getRootAliases()[0];
        $borrowerAlias                  = static::prefix('borrower');
        $borrowerMemberAlias            = static::prefix('borrowerMember');
        $borrowerParticipationPoolAlias = static::prefix('borrowerParticipationPool');
        $borrowerProjectAlias           = static::prefix('borrowerProject');

        $queryBuilder
            ->distinct()
            ->leftJoin("{$rootAlias}.pool", $borrowerParticipationPoolAlias)
            ->leftJoin("{$borrowerParticipationPoolAlias}.project", $borrowerProjectAlias)
            ->leftJoin("{$borrowerProjectAlias}.borrowers", $borrowerAlias)
            ->leftJoin("{$borrowerAlias}.members", $borrowerMemberAlias)
            ->orWhere(
                $queryBuilder->expr()->andX(
                    "{$borrowerMemberAlias}.user = :{$userParameterName}",
                    "{$borrowerParticipationPoolAlias}.secondary = :{$borrowerSecondaryParameterName}",
                    "{$borrowerProjectAlias}.currentStatus IN (:{$publishedStatusParameterName})"
                )
            )
        ;

        $token = $this->security->getToken();

        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        if (false === ($staff instanceof Staff)) {
            return;
        }

        // Variable used (need staff)
        $managedUserParameterName = static::prefix('managedUsers');
        $companyParameterName     = static::prefix('company');

        $queryBuilder
            ->setParameter($managedUserParameterName, iterator_to_array($staff->getManagedUsers(), false))
            ->setParameter($companyParameterName, $staff->getCompany())
        ;

        // Participant condition
        $participationParticipationPoolAlias    = static::prefix('participationParticipationPool');
        $participationProjectAlias              = static::prefix('participationProjectPool');
        $currentCompanyParticipationAlias       = static::prefix('currentCompanyParticipation');
        $currentCompanyParticipationMemberAlias = static::prefix('currentParticipationMember');

        $queryBuilder
            ->leftJoin("{$rootAlias}.pool", $participationParticipationPoolAlias)
            ->leftJoin("{$participationParticipationPoolAlias}.project", $participationProjectAlias)
            ->leftJoin("{$participationParticipationPoolAlias}.participations", $currentCompanyParticipationAlias) // Join to fetch currentCompanyParticipation
            ->leftJoin("{$currentCompanyParticipationAlias}.members", $currentCompanyParticipationMemberAlias)
            ->orWhere(
                $queryBuilder->expr()->andX(
                    "{$currentCompanyParticipationMemberAlias}.user IN (:{$managedUserParameterName})",
                    "{$currentCompanyParticipationAlias}.participant = :{$companyParameterName}",
                    "{$participationProjectAlias}.currentStatus IN (:{$publishedStatusParameterName})"
                )
            )
        ;

        // Agent condition
        $agentAlias                  = static::prefix('agent');
        $agentMemberAlias            = static::prefix('agentMember');
        $agentProjectAlias           = static::prefix('agentProject');
        $agentParticipationPoolAlias = static::prefix('agentParticipationPool');

        $queryBuilder
            ->leftJoin("{$rootAlias}.pool", $agentParticipationPoolAlias)
            ->leftJoin("{$agentParticipationPoolAlias}.project", $agentProjectAlias)
            ->leftJoin("{$agentProjectAlias}.agent", $agentAlias)
            ->leftJoin("{$agentAlias}.members", $agentMemberAlias)
            ->orWhere(
                $queryBuilder->expr()->andX(
                    "{$agentMemberAlias}.user IN (:{$managedUserParameterName})",
                    "{$agentAlias}.company = :{$companyParameterName}",
                )
            )
        ;
    }

    private static function prefix(string $name): string
    {
        return static::PREFIX . '_' . $name;
    }
}
