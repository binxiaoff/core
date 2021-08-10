<?php

declare(strict_types=1);

namespace KLS\Syndication\Extension\ProjectFile;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Syndication\Entity\ProjectFile;
use Symfony\Component\Security\Core\Security;

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

        $token = $this->security->getToken();

        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        if (false === ($staff instanceof Staff)) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        // External banks can't access to KYC files
        if (false === $staff->getCompany()->isCAGMember()) {
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder
                ->andWhere($rootAlias . '.type != :kyc')
                ->setParameter('kyc', ProjectFile::PROJECT_FILE_TYPE_KYC)
            ;
        }
    }
}
