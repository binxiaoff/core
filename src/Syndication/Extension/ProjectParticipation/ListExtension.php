<?php

declare(strict_types=1);

namespace Unilend\Syndication\Extension\ProjectParticipation;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Staff;
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
            ->setParameters([
                'company'            => $staff->getCompany(),
                'staff'              => $staff,
                'managedStaffMember' => $this->projectParticipationMemberRepository->findActiveByManager($staff),
                'displayableStatus'  => ProjectStatus::DISPLAYABLE_STATUSES,
            ])
        ;
    }
}
