<?php

namespace Unilend\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;

class AttachmentTypeRepository extends EntityRepository
{
    /**
     * @param array $attachmentTypes
     *
     * @return array
     */
    public function findTypesIn(array $attachmentTypes)
    {
        if (empty($attachmentTypes)) {
            return [];
        }

        $qb = $this->createQueryBuilder('at');

        return $qb->where($qb->expr()->in('at.id', ':types'))
            ->setParameter(':types', $attachmentTypes, Connection::PARAM_INT_ARRAY)
            ->orderBy('at.label', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
