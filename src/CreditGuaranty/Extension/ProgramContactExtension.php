<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\CreditGuaranty\Entity\Participation;
use Unilend\CreditGuaranty\Entity\ProgramContact;
use Unilend\CreditGuaranty\Extension\Traits\ProgramPermissionTrait;
use Unilend\CreditGuaranty\Service\StaffPermissionManager;

class ProgramContactExtension implements QueryCollectionExtensionInterface
{
    use ProgramPermissionTrait;

    private Security $security;

    public function __construct(Security $security, StaffPermissionManager $staffPermissionManager)
    {
        $this->security               = $security;
        $this->staffPermissionManager = $staffPermissionManager;
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (ProgramContact::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        $token = $this->security->getToken();
        /** @var Staff|null $staff */
        $staff              = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;
        $programAlias       = 'p';
        $participationAlias = 'pa';
        $rootAlias          = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->innerJoin("{$rootAlias}.program", $programAlias)
            ->innerJoin(Participation::class, $participationAlias, Join::WITH, "{$participationAlias}.program = {$programAlias}.id")
        ;

        $this->applyProgramManagerOrParticipantFilter($staff, $queryBuilder, $programAlias, $participationAlias);
    }
}
