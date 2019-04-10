<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{BeneficialOwner, BeneficialOwnerType, CompanyBeneficialOwnerDeclaration};

class BeneficialOwnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BeneficialOwner::class);
    }

    /**
     * @param int|CompanyBeneficialOwnerDeclaration $declaration
     * @param string                                $type
     *
     * @return int|null
     */
    public function getCountBeneficialOwnersForDeclarationByType($declaration, $type)
    {
        $queryBuilder = $this->createQueryBuilder('bo');
        $queryBuilder->select('COUNT(bo.id)')
            ->innerJoin(BeneficialOwnerType::class, 'bot', Join::WITH, 'bo.idType = bot.id')
            ->where('bo.idDeclaration = :idDeclaration')
            ->andWhere('bot.label = :label')
            ->setParameter('label', $type)
            ->setParameter('idDeclaration', $declaration);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param CompanyBeneficialOwnerDeclaration|int $declaration
     *
     * @return mixed
     */
    public function getSumPercentage($declaration)
    {
        $queryBuilder = $this->createQueryBuilder('bo');
        $queryBuilder->select('SUM(bo.percentageDetained)')
            ->where('bo.idDeclaration = :idDeclaration')
            ->setParameter('idDeclaration', $declaration);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
