<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;

class ProjectsStatusHistoryDetailsRepository extends EntityRepository
{
    public function getHistoryDetailsFromGivenStatus($projectId, $projectStatus)
    {
        $queryBuilder = $this->createQueryBuilder('pshd');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:ProjectStatusHistory', 'psh');
    }
}
