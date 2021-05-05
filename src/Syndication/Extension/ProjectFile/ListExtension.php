<?php

declare(strict_types=1);

namespace Unilend\Syndication\Extension\ProjectFile;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\ProjectFile;

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
        if (ProjectFile::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        /** @var User $user */
        $user  = $this->security->getUser();
        $staff = $user instanceof User ? $user->getCurrentStaff() : null;

        if (null === $staff) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        // External banks can't access to KYC files
        if (!$staff->getCompany()->isCAGMember()) {
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder
                ->andWhere($rootAlias . '.type != :kyc')
                ->setParameter('kyc', ProjectFile::PROJECT_FILE_TYPE_KYC)
            ;
        }
    }
}
