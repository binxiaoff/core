<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{ProjectAttachmentType, ProjectAttachmentTypeCategory};

class ProjectAttachmentTypeRepository extends EntityRepository
{
    /**
     * @return ProjectAttachmentType[]
     */
    public function getAttachmentTypes(): array
    {
        $queryBuilder = $this->createQueryBuilder('t', 't.type');
        $queryBuilder
            ->innerJoin(ProjectAttachmentTypeCategory::class, 'c', Join::WITH, 't.idCategory = c.id')
            ->orderBy('c.rank', 'ASC')
            ->addOrderBy('t.rank', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }
}
