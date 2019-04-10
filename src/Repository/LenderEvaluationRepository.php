<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\{LenderEvaluation, Wallet};

class LenderEvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LenderEvaluation::class);
    }

    /**
     * @param Wallet         $lenderWallet
     * @param \DateTime|null $date
     *
     * @return LenderEvaluation|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findValidEvaluation(Wallet $lenderWallet, ?\DateTime $date = null): ?LenderEvaluation
    {
        $today = (new \DateTime())->setTime(23, 59, 59);

        if (null === $date) {
            $date = $today;
        } else {
            $date->setTime(23, 59, 59);
        }

        $queryBuilder = $this->createQueryBuilder('le');
        $queryBuilder
            ->where('le.idLender = :lender')
            ->andWhere('le.expiryDate > :date')
            ->andWhere('le.validated <= :date')
            ->orderBy('le.validated', 'DESC')
            ->setMaxResults(1)
            ->setParameters(['lender' => $lenderWallet, 'date' => $date]);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
