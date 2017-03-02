<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class OperationRepository extends EntityRepository
{
    /**
     * @param array $criteria [field => value]
     * @param array $operator [field => operator]
     * @return array
     */
    public function getOperationsBy(array $criteria = [], array $operator = [])
    {
        $qb = $this->createQueryBuilder('op');
        $qb->select('op');

        foreach ($criteria as $field => $value) {
            $qb->andWhere('op.' . $field . $operator[$field] . ':' . $field)
                ->setParameter($field, $value);
        }
        $qb->orderBy('op.added', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $criteria [field => value]
     * @param array $operator [field => operator]
     * @return array
     */
    public function getSumOperationsBy(array $criteria = [], array $operator = [])
    {
        $qb = $this->createQueryBuilder('op');
        $qb->select('SUM(op.amount)');

        foreach ($criteria as $field => $value) {
            $qb->andWhere('op.' . $field . $operator[$field] . ':' . $field)
                ->setParameter($field, $value);
        }

        return $qb->getQuery()->getResult();
    }
}
