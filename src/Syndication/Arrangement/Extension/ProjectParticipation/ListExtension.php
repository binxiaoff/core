<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Extension\ProjectParticipation;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\Staff;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use KLS\Syndication\Arrangement\Repository\ProjectParticipationMemberRepository;
use Symfony\Component\Security\Core\Security;

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

        $token = $this->security->getToken();

        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        if (false === ($staff instanceof Staff)) {
            $queryBuilder->andWhere('1 = 0');

            return;
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
