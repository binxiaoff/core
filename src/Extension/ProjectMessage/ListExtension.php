<?php

declare(strict_types=1);

namespace Unilend\Extension\ProjectMessage;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\ProjectMessage;
use Unilend\Entity\ProjectOrganizer;

class ListExtension implements QueryCollectionExtensionInterface
{
    /** @var Security */
    private $security;

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
        if (ProjectMessage::class !== $resourceClass || $this->security->isGranted(Clients::ROLE_ADMIN)) {
            return;
        }
        /** @var Clients $user */
        $user = $this->security->getUser();
        if (!$user instanceof Clients) {
            return;
        }

        $arranger  = ProjectOrganizer::DUTY_PROJECT_ORGANIZER_ARRANGER;
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->innerJoin($rootAlias . '.participation', 'pp')
            ->leftJoin('pp.projectParticipationContacts', 'ppc')
            ->leftJoin('pp.project', 'project')
            ->leftJoin('project.organizers', 'organizer', Join::WITH, "JSON_CONTAINS(organizer.roles, '\"${$arranger}\"') = 1")
            ->andWhere('ppc.client = :client OR :company = organizer.company')
            ->setParameter('client', $user)
            ->setParameter('company', $user->getCompany())
        ;
    }
}
