<?php

declare(strict_types=1);

namespace Unilend\Syndication\Extension\NDASignature;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\NDASignature;

class ListExtension implements QueryCollectionExtensionInterface
{
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
    ): void {
        if (NDASignature::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        if (false === $staff instanceof Staff) {
            throw new RuntimeException('There should not be access to this class without a staff');
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->andWhere("{$rootAlias}.addedBy = :ndas_extension_staff")
            ->setParameter('ndas_extension_staff', $staff)
        ;
    }
}
