<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{ProjectAttachmentType, ProjectAttachmentTypeCategory};

class ProjectAttachmentTypeRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectAttachmentType::class);
    }

    /**
     * @return ProjectAttachmentType[]
     */
    public function getAttachmentTypes(): array
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder
            ->innerJoin(ProjectAttachmentTypeCategory::class, 'c', Join::WITH, 't.category = c.id')
            ->orderBy('c.rank', 'ASC')
            ->addOrderBy('t.rank', 'ASC')
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}
