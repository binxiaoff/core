<?php

declare(strict_types=1);

namespace Unilend\Api\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectStatus;

class ProjectListExtension implements QueryCollectionExtensionInterface
{
    /**
     * @var Security
     */
    private $security;

    /**
     * ProjectListExtension constructor.
     *
     * @param Security $security
     */
    public function __construct(
        Security $security
    ) {
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ): void {
        if (Project::class !== $resourceClass) {
            return;
        }

        /** @var Clients $user */
        $user = $this->security->getUser();

        $queryBuilder
            ->innerJoin('o.currentStatus', 'cpsh')
            ->where('o.submitterCompany = :company')
            ->leftJoin('o.projectParticipations', 'pp')
            ->orWhere('pp.company = :company AND cpsh.status IN (:activeStatus) AND o.marketSegment IN (:marketSegments)')
            ->leftJoin('pp.projectParticipationContacts', 'pc')
            ->orWhere('pc.client = :client')
            ->setParameters([
                'company'         => $user->getCompany(),
                'publishedStatus' => ProjectStatus::STATUS_PUBLISHED,
                'activeStatus'    => ProjectStatus::DISPLAYABLE_STATUS,
                'marketSegments'  => $user->getStaff()->getMarketSegments(),
                'client'          => $user,
            ])
        ;
    }
}
