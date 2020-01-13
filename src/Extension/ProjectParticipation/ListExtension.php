<?php

declare(strict_types=1);

namespace Unilend\Extension\ProjectParticipation;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Clients, Project, ProjectParticipation, ProjectParticipationContact};

class ListExtension implements QueryCollectionExtensionInterface
{
    /** @var Security */
    private $security;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param Security               $security
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security      = $security;
        $this->entityManager = $entityManager;
    }

    /**
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param string|null                 $operationName
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (ProjectParticipation::class !== $resourceClass || $this->security->isGranted(Clients::ROLE_ADMIN)) {
            return;
        }
        /** @var Clients $user */
        $user = $this->security->getUser();
        if (!$user instanceof Clients) {
            return;
        }

        $expressionBuilder = $this->entityManager->getExpressionBuilder();
        $subQuery          = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(s_ppc.client)')
            ->from(ProjectParticipation::class, 's_pp')
            ->leftJoin('s_pp.projectParticipationContacts', 's_ppc')
            ->where('s_pp.project = p.id')
            ->getDQL()
        ;

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->leftJoin("{$rootAlias}.projectParticipationContacts", 'ppc')
            ->leftJoin("{$rootAlias}.project", 'p')
            ->andWhere('(p.offerVisibility = :private and ppc.client = :client) OR p.offerVisibility in (:nonPrivate)')
            ->andWhere($expressionBuilder->in(':client', $subQuery))
            ->setParameter('client', $user)
            ->setParameter('private', Project::OFFER_VISIBILITY_PRIVATE)
            ->setParameter('nonPrivate', [Project::OFFER_VISIBILITY_PARTICIPANT, Project::OFFER_VISIBILITY_PUBLIC])
        ;
    }
}
