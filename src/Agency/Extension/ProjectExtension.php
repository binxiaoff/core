<?php

declare(strict_types=1);

namespace Unilend\Agency\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Staff;

class ProjectExtension implements QueryCollectionExtensionInterface
{
    private const PREFIX = 'ProjectExtension';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
        if (false === (Project::class === $resourceClass)) {
            return;
        }

        $user = $this->security->getUser();

        $token = $this->security->getToken();

        /** @var Staff $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        $rootAlias           = $queryBuilder->getRootAliases()[0];
        $borrowerAlias       = static::prefix('borrower');
        $borrowerMemberAlias = static::prefix('borrowerMember');

        $userParameterName = static::prefix('user');

        $queryBuilder
            ->distinct()
            ->leftJoin("{$rootAlias}.borrowers", $borrowerAlias)
            ->leftJoin("{$borrowerAlias}.members", $borrowerMemberAlias)
            ->orWhere("{$borrowerMemberAlias}.user = :{$userParameterName}")
            ->setParameter($userParameterName, $user)
        ;

        if ($staff) {
            $participationAlias       = static::prefix('participation');
            $participationMemberAlias = static::prefix('participationMember');

            $managedUserParameterName     = static::prefix('managedUsers');
            $companyParameterName         = static::prefix('company');
            $publishedStatusParameterName = static::prefix('publishedStatus');

            $queryBuilder
                ->leftJoin("{$rootAlias}.participations", $participationAlias)
                ->leftJoin("{$participationAlias}.members", $participationMemberAlias)
                ->orWhere(
                    $queryBuilder->expr()->andX(
                        "{$participationMemberAlias}.user IN (:{$managedUserParameterName})",
                        "{$participationAlias}.participant = :{$companyParameterName}",
                        $queryBuilder->expr()->orX(
                            "{$rootAlias}.agent = :{$companyParameterName}",
                            "{$rootAlias}.currentStatus >= :{$publishedStatusParameterName}"
                        )
                    )
                )
                ->setParameter($managedUserParameterName, iterator_to_array($staff->getManagedUsers(), false))
                ->setParameter($companyParameterName, $staff->getCompany())
                ->setParameter($publishedStatusParameterName, Project::STATUS_PUBLISHED)
            ;
        }
    }

    private static function prefix(string $name)
    {
        return static::PREFIX . '_' . $name;
    }
}
