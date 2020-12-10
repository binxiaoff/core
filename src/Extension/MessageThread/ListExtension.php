<?php

declare(strict_types=1);

namespace Unilend\Extension\MessageThread;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\{Expr\Join};
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\MessageThread;

class ListExtension implements QueryCollectionExtensionInterface
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (MessageThread::class !== $resourceClass || $this->security->isGranted(Clients::ROLE_ADMIN)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof Clients) {
            return;
        }
        $staff = $user->getCurrentStaff();

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->distinct()
            ->innerJoin($rootAlias . '.messages', 'msg')
            ->innerJoin('msg.messageStatuses', 'msgst')
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('msg.sender', ':staff'),
                $queryBuilder->expr()->eq('msgst.recipient', ':staff')
            ))
            ->setParameter('staff', $staff);
    }
}
