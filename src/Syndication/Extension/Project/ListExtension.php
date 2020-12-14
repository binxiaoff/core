<?php

declare(strict_types=1);

namespace Unilend\Syndication\Extension\Project;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\{Project, ProjectStatus};
use Unilend\Syndication\Repository\ProjectParticipationMemberRepository;

class ListExtension implements QueryCollectionExtensionInterface
{
    /** @var Security */
    private Security $security;
    /** @var ProjectParticipationMemberRepository */
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;

    /**
     * @param Security                             $security
     * @param ProjectParticipationMemberRepository $projectParticipationMemberRepository
     */
    public function __construct(
        Security $security,
        ProjectParticipationMemberRepository $projectParticipationMemberRepository
    ) {
        $this->security = $security;
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (Project::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            throw new RuntimeException('There should not be access to this class without a staff');
        }


        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->distinct()
            ->innerJoin($rootAlias . '.currentStatus', 'cs')
            ->leftJoin($rootAlias . '.projectParticipations', 'pp')
            ->leftJoin('pp.projectParticipationMembers', 'ppc')
            ->andWhere($queryBuilder->expr()->orX(
                // if you are owner
                $rootAlias . '.submitterUser = :user',
                // or you are non archived participant and the project is published
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->andX('ppc.staff = :staff', 'ppc.archived IS NULL'), // You are non archived member of participation
                        'ppc IN (:managedStaffMember)', // You managed a member of a participation
                    ),
                    $queryBuilder->expr()->orX(
                        $rootAlias . '.submitterCompany = pp.participant', // you are in arranger company
                        'cs.status in (:displayableStatus)' // or your participant and the project is in displayable status
                    )
                )
            ))
            ->setParameter('user', $staff->getUser())
            ->setParameter('displayableStatus', ProjectStatus::DISPLAYABLE_STATUSES)
            ->setParameter('staff', $staff)
            ->setParameter('managedStaffMember', $this->projectParticipationMemberRepository->findByManager($staff))
        ;
    }
}
