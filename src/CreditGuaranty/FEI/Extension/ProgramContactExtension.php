<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\CreditGuaranty\FEI\Entity\Participation;
use KLS\CreditGuaranty\FEI\Entity\ProgramContact;
use KLS\CreditGuaranty\FEI\Extension\Traits\ProgramPermissionTrait;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;
use Symfony\Component\Security\Core\Security;

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
            ->leftJoin(Participation::class, $participationAlias, Join::WITH, "{$participationAlias}.program = {$programAlias}.id")
        ;

        $this->applyProgramManagerOrParticipantFilter($staff, $queryBuilder, $programAlias, $participationAlias);
    }
}
