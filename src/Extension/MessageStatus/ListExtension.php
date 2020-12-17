<?php

declare(strict_types=1);

namespace Unilend\Extension\MessageStatus;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{Clients, MessageStatus, MessageThread, Project, ProjectParticipation, ProjectStatus};

class ListExtension implements QueryCollectionExtensionInterface
{
    /**
     * @var Security
     */
    private Security $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param string|null                 $operationName
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (MessageStatus::class !== $resourceClass || $this->security->isGranted(Clients::ROLE_ADMIN)) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof Clients) {
            return;
        }
        $staff = $user->getCurrentStaff();

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere($queryBuilder->expr()->eq($rootAlias . '.recipient', ':staff'))
            ->setParameter('staff', $staff)
            ->innerJoin($rootAlias . '.message', 'msg')
            ->innerJoin(MessageThread::class, 'msgtd', Join::WITH, 'msg.messageThread = msgtd.id')
            ->innerJoin(ProjectParticipation::class, 'pp', Join::WITH, 'msgtd.projectParticipation = pp.id')
            ->innerJoin(Project::class, 'p', Join::WITH, 'pp.project = p.id')
            ->innerJoin(ProjectStatus::class, 'pst', Join::WITH, 'p.currentStatus = pst.id')
            ->andWhere(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq($rootAlias . '.recipient', ':staff'),
                    $queryBuilder->expr()->gt('pst.status', ':project_current_status')
                )
            )
            ->setParameters([
                'staff' => $staff,
                'project_current_status' => ProjectStatus::STATUS_DRAFT,
            ])
            ->orderBy('msg.messageThread', 'ASC');
    }
}
