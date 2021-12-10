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
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Extension\Traits\ProgramPermissionTrait;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;
use Symfony\Component\Security\Core\Security;

class ProgramEligibilityExtension implements QueryCollectionExtensionInterface
{
    use ProgramPermissionTrait;

    private Security $security;

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
        if (ProgramEligibility::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        $token = $this->security->getToken();
        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        $programAlias       = 'p';
        $participationAlias = 'pa';

        $queryBuilder
            // distinct keyword allows retrieving expected results
            // otherwise it can return a part of results
            // because of the left join (duplicates) and the limit by default to 60
            ->distinct()
            ->innerJoin("{$queryBuilder->getRootAliases()[0]}.program", $programAlias)
            // it needs to join participation to get programEligibility fields for generating reservation request forms
            ->leftJoin(
                Participation::class,
                $participationAlias,
                Join::WITH,
                "{$participationAlias}.program = {$programAlias}.id"
            )
        ;
        $this->applyProgramManagerOrParticipantFilter($staff, $queryBuilder, $programAlias, $participationAlias);
    }
}
