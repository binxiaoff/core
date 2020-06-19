<?php

declare(strict_types=1);

namespace Unilend\Extension\ProjectParticipation;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Clients, Project, ProjectParticipation, ProjectStatus};

class ListExtension implements QueryCollectionExtensionInterface
{
    /** @var Security */
    private $security;
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param Security               $security
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security      = $security;
        $this->entityManager = $entityManager;
    }

    /**
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param string|null                 $operationName
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (ProjectParticipation::class !== $resourceClass) {
            return;
        }
        /** @var Clients $user */
        $user = $this->security->getUser();
        if (!$user instanceof Clients) {
            return;
        }

        $staff = $user->getCurrentStaff();

        $expressionBuilder = $this->entityManager->getExpressionBuilder();
        $subQueryBuilder   = $this->entityManager->createQueryBuilder();
        $subQueryBuilder->select('sub_project')
            ->from(Project::class, 'sub_project')
            ->innerJoin('sub_project.currentStatus', 'sub_cs')
            ->innerJoin('sub_project.projectParticipations', 'sub_participation')
            ->innerJoin('sub_participation.projectParticipationContacts', 'sub_contact')
            ->where('sub_contact.client = :client AND sub_cs.status in (:displayableStatus)')
        ;
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->distinct()
            ->leftJoin("{$rootAlias}.projectParticipationContacts", 'ppc')
            ->innerJoin("{$rootAlias}.project", 'p')
            ->innerJoin('p.organizers', 'organizers')
            ->andWhere(
                $expressionBuilder->orX(
                    // Submitter condition
                    $expressionBuilder->andX(
                        'p.submitterCompany = :company',
                        $queryBuilder->expr()->orX(
                            'p.marketSegment IN (:marketSegments)',
                            ($staff && $staff->isAdmin() ? '1 = 1' : '0 = 1')
                        )
                    ),
                    // Participant condition
                    $expressionBuilder->andX(
                        'ppc.archived IS NULL',
                        '(p.offerVisibility = :private AND ppc.client = :client) OR p.offerVisibility in (:nonPrivate)',
                        $expressionBuilder->in('p.id', $subQueryBuilder->getDQL())
                    )
                )
            )
            ->setParameter('client', $user)
            ->setParameter('private', Project::OFFER_VISIBILITY_PRIVATE)
            ->setParameter('displayableStatus', ProjectStatus::DISPLAYABLE_STATUS)
            ->setParameter('nonPrivate', [Project::OFFER_VISIBILITY_PARTICIPANT, Project::OFFER_VISIBILITY_PUBLIC])
            ->setParameter('company', $user->getCompany())
            ->setParameter('marketSegments', $user->getCurrentStaff()->getMarketSegments())
        ;
    }
}
