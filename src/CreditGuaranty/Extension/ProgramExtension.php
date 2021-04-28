<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\CreditGuaranty\Service\StaffPermissionManager;

class ProgramExtension implements QueryCollectionExtensionInterface
{
    private Security               $security;
    private StaffPermissionManager $staffPermissionManager;

    public function __construct(Security $security, StaffPermissionManager $staffPermissionManager)
    {
        $this->security               = $security;
        $this->staffPermissionManager = $staffPermissionManager;
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (Program::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }
        $token = $this->security->getToken();
        /** @var Staff $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        if (null === $staff) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->distinct()
            ->andWhere("{$rootAlias}.managingCompany = :managingCompany")
            ->setParameter('managingCompany', $staff->getCompany())
        ;

        if (false === $staff->isAdmin()) {
            $queryBuilder
                ->andWhere("{$rootAlias}.companyGroupTag in (:companyGroupTags)")
                ->setParameter('companyGroupTags', $staff->getCompanyGroupTags())
            ;
        }

        if (false === $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_READ_PROGRAM)) {
            $queryBuilder->andWhere('1 = 0');
        }

        if ($staff->isManager()) {
            $queryBuilder
                ->orWhere("{$rootAlias}.addedBy in (:managedUsers)")
                ->setParameter('managedUsers', iterator_to_array($staff->getManagedUsers(), false))
            ;
        }
    }
}
