<?php

declare(strict_types=1);

namespace KLS\Core\Extension\MessageStatus;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\MessageStatus;
use KLS\Core\Entity\MessageThread;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Syndication\Entity\Project;
use KLS\Syndication\Entity\ProjectParticipation;
use KLS\Syndication\Entity\ProjectStatus;
use Symfony\Component\Security\Core\Security;

class ListExtension implements QueryCollectionExtensionInterface
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
        if (MessageStatus::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
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
            ->innerJoin($rootAlias . '.message', 'msg')
            ->innerJoin(MessageThread::class, 'msgtd', Join::WITH, 'msg.messageThread = msgtd.id')
            ->innerJoin(ProjectParticipation::class, 'pp', Join::WITH, 'msgtd.projectParticipation = pp.id')
            ->innerJoin(Project::class, 'p', Join::WITH, 'pp.project = p.id')
            ->innerJoin(ProjectStatus::class, 'pst', Join::WITH, 'p.currentStatus = pst.id')
            ->andWhere($rootAlias . '.recipient = :staff')
            ->andWhere('pst.status > :project_current_status')
            ->setParameter('staff', $staff)
            ->setParameter('project_current_status', ProjectStatus::STATUS_DRAFT)
            ->orderBy('msg.messageThread', 'ASC')
        ;
    }
}
