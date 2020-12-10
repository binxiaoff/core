<?php

declare(strict_types=1);

namespace Unilend\Extension\Message;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Message;
use Unilend\Entity\Clients;

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
        if (Message::class !== $resourceClass) {
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
            ->innerJoin($rootAlias . '.messageStatuses', 'msgst')
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq($rootAlias . '.sender', ':staff'),
                $queryBuilder->expr()->eq('msgst.recipient', ':staff')
            ))
            ->setParameter('staff', $staff);
    }
}
