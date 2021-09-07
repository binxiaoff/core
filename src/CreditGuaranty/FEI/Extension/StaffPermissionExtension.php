<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use Symfony\Component\Security\Core\Security;

class StaffPermissionExtension implements QueryCollectionExtensionInterface
{
    private Security               $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (StaffPermission::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        $token = $this->security->getToken();
        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;
        if (null === $staff) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->innerJoin("{$rootAlias}.staff", 's')
            ->andWhere('s.team in (:teams)')
            ->setParameter('teams', \iterator_to_array($staff->getCompany()->getTeams(), false))
        ;
    }
}
