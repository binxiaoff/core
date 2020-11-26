<?php

declare(strict_types=1);

namespace Unilend\Extension\ProjectFile;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\AcceptationsLegalDocs;
use Unilend\Core\Entity\Clients;
use Unilend\Entity\{ProjectFile};

class ListExtension implements QueryCollectionExtensionInterface
{
    /** @var Security */
    private Security $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param string|null                 $operationName
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (ProjectFile::class !== $resourceClass || $this->security->isGranted(Clients::ROLE_ADMIN)) {
            return;
        }

        /** @var Clients $user */
        $user  = $this->security->getUser();
        $staff = $user instanceof Clients ? $user->getCurrentStaff() : null;

        // External banks can't access to KYC files
        if (!$staff->getCompany()->isCAGMember()) {
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder
                ->andWhere($rootAlias . '.type != :kyc')
                ->setParameter('kyc', ProjectFile::PROJECT_FILE_TYPE_KYC);
        }
    }
}
