<?php

declare(strict_types=1);

namespace Unilend\Core\Extension\MessageThread;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\{Message, MessageStatus, MessageThread, User};
use Unilend\Syndication\Entity\{Project, ProjectParticipation, ProjectParticipationMember, ProjectStatus};

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
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (MessageThread::class !== $resourceClass) {
            return;
        }

        $staff = $user->getCurrentStaff();

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->innerJoin(ProjectParticipation::class, 'pp', Join::WITH, $rootAlias . '.projectParticipation = pp.id')
            ->innerJoin(Project::class, 'p', Join::WITH, 'p.id = pp.project')
            ->innerJoin(ProjectStatus::class, 'pst', Join::WITH, 'pst.project = p.id')
            ->innerJoin(Message::class, 'msg', Join::WITH, $rootAlias . '.id = msg.messageThread')
            ->leftJoin(MessageStatus::class, 'msgst', Join::WITH, 'msg.id = msgst.message')
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('msgst.recipient', ':recipient'),
                $queryBuilder->expr()->eq('msg.sender', ':sender')
            ))
            ->andWhere('pst.status > :project_current_status')
            ->setParameters([
                'recipient'              => $staff,
                'sender'                 => $staff,
                'project_current_status' => ProjectStatus::STATUS_DRAFT,
            ])
            ->orderBy('p.title', 'ASC');
    }
}
