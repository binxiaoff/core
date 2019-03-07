<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\CloseOutNettingRepayment;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;

class CloseOutNettingRepaymentRepository extends EntityRepository
{
    /**
     * @param Projects|int $project
     *
     * @return CloseOutNettingRepayment[]
     */
    public function findByProject($project)
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:Loans', 'l', Join::WITH, 'c.idLoan = l.idLoan')
            ->where('l.project = :project')
            ->setParameter('project', $project);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Projects|int $project
     *
     * @return array
     */
    public function getNotRepaidAmountByProject($project)
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder->select('SUM(c.capital - c.repaidCapital) as capital, SUM(c.interest - c.repaidInterest) as interest')
            ->innerJoin('UnilendCoreBusinessBundle:Loans', 'l', Join::WITH, 'c.idLoan = l.idLoan')
            ->where('l.project = :project')
            ->setParameter('project', $project);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param Projects|int $project
     *
     * @return float
     */
    public function getNotRepaidCapitalByProject($project)
    {
        $amount = $this->getNotRepaidAmountByProject($project);

        return $amount['capital'];
    }

    /**
     * @param Projects|int $project
     *
     * @return float
     */
    public function getNotRepaidInterestByProject($project)
    {
        $amount = $this->getNotRepaidAmountByProject($project);

        return $amount['interest'];
    }

    /**
     * @param Loans|int|array $loans
     *
     * @return float
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getRemainingCapitalByLoan($loans): float
    {
        if (false === is_array($loans)) {
            $loans = [$loans];
        }
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder->select('SUM(c.capital - c.repaidCapital)')
            ->where('c.idLoan in (:loan)')
            ->setParameter('loan', $loans);

        return (float) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
