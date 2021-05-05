<?php

declare(strict_types=1);

namespace Unilend\Syndication\Extension\ProjectParticipation;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectStatus;
use Unilend\Syndication\Repository\ProjectParticipationMemberRepository;

class ListExtension implements QueryCollectionExtensionInterface
{
    private Security $security;
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;

    public function __construct(Security $security, ProjectParticipationMemberRepository $projectParticipationMemberRepository)
    {
        $this->security                             = $security;
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ): void {
        if (ProjectParticipation::class !== $resourceClass) {
            return;
        }

        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        if (false === $staff instanceof Staff) {
            throw new RuntimeException('There should not be access to this class without a staff');
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->distinct()
            ->leftJoin("{$rootAlias}.projectParticipationMembers", 'ppc')
            ->innerJoin("{$rootAlias}.project", 'p')
            ->innerJoin('p.currentStatus', 'cs')
            ->andWhere(
                // you fulfill both of the following conditions :
                $queryBuilder->expr()->andX(
                    // you are non archived member of participation OR you managed a member of a participation
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->andX('ppc.staff = :staff', 'ppc.archived IS NULL'),
                        'ppc IN (:managedStaffMember)'
                    ),
                    // you are in arranger company OR your participant and the project is in displayable status
                    $queryBuilder->expr()->orX(
                        'p.submitterCompany = ' . $rootAlias . '.participant',
                        $queryBuilder->expr()->andX('cs.status IN (:displayableStatus)', $rootAlias . '.participant = :company')
                    ),
                )
            )
            ->setParameter('company', $staff->getCompany())
            ->setParameter('staff', $staff)
            ->setParameter('managedStaffMember', $this->projectParticipationMemberRepository->findActiveByManager($staff))
            ->setParameter('displayableStatus', ProjectStatus::DISPLAYABLE_STATUSES)
        ;
    }
}
