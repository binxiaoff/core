<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\QueryException;
use Unilend\Entity\{AttachmentType, ProjectAttachmentType, ProjectAttachmentTypeCategory};

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
     * @throws QueryException
     *
     * @return ProjectAttachmentType[]
     */
    public function getAttachmentTypes(): array
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder
            ->innerJoin(ProjectAttachmentTypeCategory::class, 'c', Join::WITH, 't.category = c.id')
            ->innerJoin(AttachmentType::class, 'at', Join::WITH, 't.attachmentType = at.id')
            ->orderBy('c.rank', 'ASC')
            ->addOrderBy('t.rank', 'ASC')
//            ->indexBy('at', 'at.id')
        ;
//var_dump($queryBuilder->getQuery()->getResult());die;
        return $queryBuilder->getQuery()->getResult();
    }
}
