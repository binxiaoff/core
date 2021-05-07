<?php

declare(strict_types=1);

namespace Unilend\Core\Extension\MessageStatus;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\MessageStatus;
use Unilend\Core\Entity\MessageThread;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectStatus;

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
            ->setParameters([
                'staff'                  => $staff,
                'project_current_status' => ProjectStatus::STATUS_DRAFT,
            ])
            ->orderBy('msg.messageThread', 'ASC')
        ;
    }
}
