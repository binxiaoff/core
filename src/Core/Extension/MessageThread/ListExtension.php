<?php

declare(strict_types=1);

namespace Unilend\Core\Extension\MessageThread;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\{MessageThread, User};
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
            ->innerJoin(ProjectParticipationMember::class, 'ppm', Join::WITH, 'ppm.projectParticipation = pp.id')
            ->andWhere('ppm.staff = :staff')
            ->andWhere('pst.status > :project_current_status')
            ->setParameters([
                'staff' => $staff,
                'project_current_status' => ProjectStatus::STATUS_DRAFT,
            ])
            ->orderBy('p.title', 'ASC');
    }
}
