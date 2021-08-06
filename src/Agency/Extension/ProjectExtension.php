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
        if (false === (Project::class === $resourceClass) || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        $user = $this->security->getUser();

        if (false === ($user instanceof User)) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $userParameterName            = $queryNameGenerator->generateParameterName('user');
        $publishedStatusParameterName = $queryNameGenerator->generateParameterName('publishedStatus');

        // Borrower condition
        $rootAlias           = $queryBuilder->getRootAliases()[0];
        $borrowerAlias       = $queryNameGenerator->generateJoinAlias('borrower');
        $borrowerMemberAlias = $queryNameGenerator->generateJoinAlias('borrowerMember');

        $queryBuilder
            ->distinct()
            ->leftJoin("{$rootAlias}.borrowers", $borrowerAlias)
            ->leftJoin("{$borrowerAlias}.members", $borrowerMemberAlias)
            ->orWhere($queryBuilder->expr()->andX(
                "{$borrowerMemberAlias}.user = :{$userParameterName}",
                "{$borrowerMemberAlias}.archivingDate IS NULL",
                "{$rootAlias}.currentStatus IN (:{$publishedStatusParameterName})"
            ))
            ->setParameter($userParameterName, $user)
            ->setParameter($publishedStatusParameterName, [Project::STATUS_PUBLISHED, Project::STATUS_ARCHIVED, Project::STATUS_FINISHED])
        ;

        $token = $this->security->getToken();

        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        if (false === ($staff instanceof Staff)) {
            return;
        }

        $managedUserParameterName = $queryNameGenerator->generateParameterName('managedUsers');
        $companyParameterName     = $queryNameGenerator->generateParameterName('company');

        // Participant condition
        $participationAlias       = $queryNameGenerator->generateJoinAlias('participation');
        $participationPoolAlias   = $queryNameGenerator->generateJoinAlias('participationPool');
        $participationMemberAlias = $queryNameGenerator->generateJoinAlias('participationMember');

        $queryBuilder
            ->leftJoin("{$rootAlias}.participationPools", $participationPoolAlias)
            ->leftJoin("{$participationPoolAlias}.participations", $participationAlias)
            ->leftJoin("{$participationAlias}.members", $participationMemberAlias)
            ->orWhere(
                $queryBuilder->expr()->andX(
                    "{$participationMemberAlias}.user IN (:{$managedUserParameterName})",
                    "{$participationMemberAlias}.archivingDate IS NULL",
                    "{$participationAlias}.participant = :{$companyParameterName}",
                    "{$rootAlias}.currentStatus IN (:{$publishedStatusParameterName})"
                )
            )
        ;

        // Agent condition
        $agentAlias       = $queryNameGenerator->generateJoinAlias('agent');
        $agentMemberAlias = $queryNameGenerator->generateJoinAlias('agentMember');

        $queryBuilder
            ->leftJoin("{$rootAlias}.agent", $agentAlias)
            ->leftJoin("{$agentAlias}.members", $agentMemberAlias)
            ->orWhere(
                $queryBuilder->expr()->andX(
                    "{$agentMemberAlias}.user IN (:{$managedUserParameterName})",
                    "{$agentMemberAlias}.archivingDate IS NULL",
                    "{$agentAlias}.company = :{$companyParameterName}",
                )
            )
        ;

        $queryBuilder
            ->setParameter($managedUserParameterName, \iterator_to_array($staff->getManagedUsers(), false))
            ->setParameter($companyParameterName, $staff->getCompany())
        ;
    }
}
