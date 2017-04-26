<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;

class ProjectsRepository extends EntityRepository
{
    /**
     * @param array $companies
     *
     * @return Projects[]
     */
    public function getPartnerProjects(array $companies)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        return $queryBuilder
            ->where(
                $queryBuilder->expr()->gte('p.status', \projects_status::INCOMPLETE_REQUEST)
            )
            ->andWhere('p.idCompanySubmitter IN (:companies)')
            ->setParameter('companies', $companies)
            ->orderBy('p.added', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
