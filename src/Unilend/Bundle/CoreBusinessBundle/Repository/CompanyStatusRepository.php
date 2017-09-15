<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;

class CompanyStatusRepository extends EntityRepository
{
    /**
     * @param array $status
     *
     * @return array
     */
    public function findCompanyStatusByLabel(array $status)
    {
        $queryBuilder = $this->createQueryBuilder('cs')
            ->where('cs.label IN (:status)')
            ->setParameter('status', $status, Connection::PARAM_STR_ARRAY)
            ->orderBy('cs.id', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }
}
