<?php

declare(strict_types=1);

namespace Unilend\Syndication\Extension\ProjectMessage;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\ProjectMessage;

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
        if (ProjectMessage::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
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

        if (!$staff instanceof Staff) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->distinct()
            ->innerJoin($rootAlias . '.participation', 'pp')
            ->leftJoin('pp.projectParticipationMembers', 'ppc')
            ->leftJoin('pp.project', 'project')
            ->andWhere('(ppc.staff = :staff AND ppc.archived IS NULL) OR :company = organizer.company')
            ->setParameter('staff', $staff)
            ->setParameter('company', $staff->getCompany())
        ;
    }
}
