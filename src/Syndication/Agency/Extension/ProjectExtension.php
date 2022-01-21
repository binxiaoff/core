<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\ContextAwareQueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Filter\ApiPlatform\ProjectFilter;
use Symfony\Component\Security\Core\Security;

class ProjectExtension implements ContextAwareQueryCollectionExtensionInterface
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
        string $operationName = null,
        array $context = []
    ): void {
        if (false === (Project::class === $resourceClass) || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        $user = $this->security->getUser();

        if (false === ($user instanceof User)) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $filterAs = ProjectFilter::normaliseFilter($context);

        $queryBuilder->distinct();

        if (empty($filterAs) || \in_array(ProjectFilter::VALUE_BORROWER, $filterAs, true)) {
            $this->borrowerQuery($queryBuilder, $queryNameGenerator, $user);
        }

        if (empty($filterAs) || \in_array(ProjectFilter::VALUE_PARTICIPANT, $filterAs, true)) {
            $this->participantQuery($queryBuilder, $queryNameGenerator);
        }

        if (empty($filterAs) || \in_array(ProjectFilter::VALUE_AGENT, $filterAs, true)) {
            $this->agentQuery($queryBuilder, $queryNameGenerator);
        }
    }

    private function borrowerQuery(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        User $user
    ): void {
        $rootAlias                    = $queryBuilder->getRootAliases()[0];
        $borrowerAlias                = $queryNameGenerator->generateJoinAlias('borrower');
        $borrowerMemberAlias          = $queryNameGenerator->generateJoinAlias('borrowerMember');
        $userParameterName            = $queryNameGenerator->generateParameterName('user');
        $publishedStatusParameterName = $queryNameGenerator->generateParameterName('publishedStatus');

        $queryBuilder
            ->leftJoin("{$rootAlias}.borrowers", $borrowerAlias)
            ->leftJoin("{$borrowerAlias}.members", $borrowerMemberAlias)
            ->orWhere($queryBuilder->expr()->andX(
                "{$borrowerMemberAlias}.user = :{$userParameterName}",
                "{$borrowerMemberAlias}.archivingDate IS NULL",
                "{$rootAlias}.currentStatus IN (:{$publishedStatusParameterName})"
            ))
            ->setParameter($userParameterName, $user)
            ->setParameter($publishedStatusParameterName, [
                Project::STATUS_PUBLISHED, Project::STATUS_ARCHIVED, Project::STATUS_FINISHED,
            ])
        ;
    }

    private function participantQuery(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        $staff = $this->getCurrentStaff();
        if (null === $staff) {
            return;
        }
        $managedUser = $staff->getManagedUsers();

        $rootAlias                    = $queryBuilder->getRootAliases()[0];
        $participationAlias           = $queryNameGenerator->generateJoinAlias('participation');
        $participationPoolAlias       = $queryNameGenerator->generateJoinAlias('participationPool');
        $participationMemberAlias     = $queryNameGenerator->generateJoinAlias('participationMember');
        $publishedStatusParameterName = $queryNameGenerator->generateParameterName('publishedStatus');
        $managedUserParameterName     = $queryNameGenerator->generateParameterName('managedUsers');
        $companyParameterName         = $queryNameGenerator->generateParameterName('company');

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
            ->setParameter($publishedStatusParameterName, [
                Project::STATUS_PUBLISHED, Project::STATUS_ARCHIVED, Project::STATUS_FINISHED,
            ])
            ->setParameter($managedUserParameterName, \iterator_to_array($managedUser, false))
            ->setParameter($companyParameterName, $staff->getCompany())
        ;
    }

    private function agentQuery(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        $staff = $this->getCurrentStaff();
        if (null === $staff) {
            return;
        }
        $managedUser = $staff->getManagedUsers();

        $rootAlias                = $queryBuilder->getRootAliases()[0];
        $agentAlias               = $queryNameGenerator->generateJoinAlias('agent');
        $agentMemberAlias         = $queryNameGenerator->generateJoinAlias('agentMember');
        $managedUserParameterName = $queryNameGenerator->generateParameterName('managedUsers');
        $companyParameterName     = $queryNameGenerator->generateParameterName('company');

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
            ->setParameter($managedUserParameterName, \iterator_to_array($managedUser, false))
            ->setParameter($companyParameterName, $staff->getCompany())
        ;
    }

    private function getCurrentStaff(): ?Staff
    {
        $token = $this->security->getToken();

        return ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;
    }
}
