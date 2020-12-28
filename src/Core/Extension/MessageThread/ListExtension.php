<?php

declare(strict_types=1);

namespace Unilend\Core\Extension\MessageThread;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\{Message, MessageStatus, MessageThread, User};
use Unilend\Syndication\Entity\{Project, ProjectParticipation, ProjectStatus};
use Unilend\Core\Repository\MessageThreadRepository;

class ListExtension implements QueryCollectionExtensionInterface
{
    /** @var Security */
    private $security;

    /** @var MessageThreadRepository */
    private $messageThreadRepository;

    /**
     * ListExtension constructor.
     *
     * @param Security                $security
     * @param MessageThreadRepository $messageThreadRepository
     */
    public function __construct(Security $security, MessageThreadRepository $messageThreadRepository)
    {
        $this->security                = $security;
        $this->messageThreadRepository = $messageThreadRepository;
    }

    /**
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param string|null                 $operationName
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        if (MessageThread::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        $staff = $user->getCurrentStaff();
        if (null === $staff) {
            return;
        }

        $expressionBuilder = $this->messageThreadRepository->getEntityManager()->getExpressionBuilder();
        $subQueryBuilder   = $this->messageThreadRepository->getEntityManager()->createQueryBuilder();
        $subQueryBuilder->select('sub_project')
            ->from(Project::class, 'sub_project')
            ->innerJoin('sub_project.currentStatus', 'sub_cs')
            ->innerJoin('sub_project.projectParticipations', 'sub_participation')
            ->innerJoin('sub_participation.projectParticipationMembers', 'sub_member')
            ->where('sub_member.staff = :staff')
        ;

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->innerJoin(ProjectParticipation::class, 'pp', Join::WITH, $rootAlias . '.projectParticipation = pp.id')
            ->leftJoin("pp.projectParticipationMembers", 'ppc')
            ->innerJoin(Project::class, 'p', Join::WITH, 'p.id = pp.project')
            ->innerJoin(ProjectStatus::class, 'pst', Join::WITH, 'pst.project = p.id')
            ->innerJoin(Message::class, 'msg', Join::WITH, $rootAlias . '.id = msg.messageThread')
            ->leftJoin(MessageStatus::class, 'msgst', Join::WITH, 'msg.id = msgst.message')
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('msgst.recipient', ':staff'),
                $queryBuilder->expr()->eq('msg.sender', ':staff')
            ))
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
                        '(p.offerVisibility = :private AND ppc.staff = :staff) OR p.offerVisibility in (:nonPrivate)',
                        $expressionBuilder->in('p.id', $subQueryBuilder->getDQL())
                    )
                )
            )
            ->setParameter('staff', $staff)
            ->setParameter('private', Project::OFFER_VISIBILITY_PRIVATE)
            ->setParameter('nonPrivate', [Project::OFFER_VISIBILITY_PARTICIPANT, Project::OFFER_VISIBILITY_PUBLIC])
            ->setParameter('company', $user->getCompany())
            ->setParameter('marketSegments', $user->getCurrentStaff()->getMarketSegments())
            ->orderBy('p.title', 'ASC');
    }
}
