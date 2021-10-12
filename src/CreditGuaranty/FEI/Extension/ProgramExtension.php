<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Extension\Traits\ProgramPermissionTrait;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;
use Symfony\Component\Security\Core\Security;

class ProgramExtension implements QueryCollectionExtensionInterface
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
        if (Program::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }
        $token = $this->security->getToken();
        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        $programAlias       = $queryBuilder->getRootAliases()[0];
        $participationAlias = $queryNameGenerator->generateJoinAlias('participations');
        $queryBuilder->leftJoin("{$programAlias}.participations", $participationAlias);

        $this->applyProgramManagerOrParticipantFilter($staff, $queryBuilder, $programAlias, $participationAlias);
    }
}
