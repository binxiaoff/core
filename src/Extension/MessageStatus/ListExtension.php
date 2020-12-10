<?php

declare(strict_types=1);

namespace Unilend\Extension\MessageStatus;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\MessageStatus;

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
            ->setParameter('staff', $staff);
    }
}
