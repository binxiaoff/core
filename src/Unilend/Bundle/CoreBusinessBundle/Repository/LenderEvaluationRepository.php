<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\LenderEvaluation;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

class LenderEvaluationRepository extends EntityRepository
{
    /**
     * @param Wallet                   $lenderWallet
     * @param \DateTime|null           $date
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
