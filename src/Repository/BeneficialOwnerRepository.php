<?php

namespace Unilend\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{BeneficialOwnerType, CompanyBeneficialOwnerDeclaration};

class BeneficialOwnerRepository extends EntityRepository
{
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
