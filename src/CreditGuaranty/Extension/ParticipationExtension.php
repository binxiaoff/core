<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\CreditGuaranty\Entity\Participation;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\CreditGuaranty\Service\StaffPermissionManager;

class ParticipationExtension implements QueryCollectionExtensionInterface
{
    private Security               $security;
    private StaffPermissionManager $staffPermissionManager;

    public function __construct(Security $security, StaffPermissionManager $staffPermissionManager)
    {
        $this->security               = $security;
        $this->staffPermissionManager = $staffPermissionManager;
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ): void {
        if (Participation::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        $token = $this->security->getToken();
        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        if (null === $staff || false === $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_READ_PROGRAM)) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->distinct()
            ->innerJoin("{$rootAlias}.program", 'p')
            ->andWhere("{$rootAlias}.participant = :company OR p.managingCompany = :company")
            ->setParameter('company', $staff->getCompany())
        ;
    }
}
