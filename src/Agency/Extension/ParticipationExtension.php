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
        $userParameterName              = $queryNameGenerator->generateParameterName('user');
        $borrowerSecondaryParameterName = $queryNameGenerator->generateParameterName('borrowerSecondary');
        $publishedStatusParameterName   = $queryNameGenerator->generateParameterName('publishedParameterName');

        $queryBuilder
            ->setParameter($userParameterName, $user)
            ->setParameter($borrowerSecondaryParameterName, false)
            ->setParameter($publishedStatusParameterName, [Project::STATUS_PUBLISHED, Project::STATUS_ARCHIVED])
        ;

        // Borrower condition
        $rootAlias                      = $queryBuilder->getRootAliases()[0];
        $borrowerAlias                  = $queryNameGenerator->generateJoinAlias('borrower');
        $borrowerMemberAlias            = $queryNameGenerator->generateJoinAlias('borrowerMember');
        $borrowerParticipationPoolAlias = $queryNameGenerator->generateJoinAlias('borrowerParticipation');
        $borrowerProjectAlias           = $queryNameGenerator->generateJoinAlias('borrowerProject');

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
        $managedUserParameterName = $queryNameGenerator->generateParameterName('managedUsers');
        $companyParameterName     = $queryNameGenerator->generateParameterName('company');

        $queryBuilder
            ->setParameter($managedUserParameterName, iterator_to_array($staff->getManagedUsers(), false))
            ->setParameter($companyParameterName, $staff->getCompany())
        ;

        // Participant condition
        $participationParticipationPoolAlias    = $queryNameGenerator->generateJoinAlias('participationPool');
        $participationProjectAlias              = $queryNameGenerator->generateJoinAlias('participationProject');
        $currentCompanyParticipationAlias       = $queryNameGenerator->generateJoinAlias('currentCompanyParticipation');
        $currentCompanyParticipationMemberAlias = $queryNameGenerator->generateJoinAlias('currentCompanyParticipationMember');

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
        $agentAlias                  = $queryNameGenerator->generateJoinAlias('agent');
        $agentMemberAlias            = $queryNameGenerator->generateJoinAlias('agentMember');
        $agentProjectAlias           = $queryNameGenerator->generateJoinAlias('agentProject');
        $agentParticipationPoolAlias = $queryNameGenerator->generateJoinAlias('agentParticipation');

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
}
