<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Extension\Traits\ProgramPermissionTrait;
use Symfony\Component\Security\Core\Security;

class ReportingTemplateExtension implements QueryCollectionExtensionInterface
{
    use ProgramPermissionTrait;

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if (ReportingTemplate::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        $token = $this->security->getToken();
        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        if (null === $staff || false === $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_REPORTING)) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $programAlias = 'p';

        $queryBuilder
            ->innerJoin("{$queryBuilder->getRootAliases()[0]}.program", $programAlias)
        ;

        $this->applyProgramManagerFilter($staff, $queryBuilder, $programAlias);
    }
}
