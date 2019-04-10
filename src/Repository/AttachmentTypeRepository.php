<?php

namespace Unilend\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Unilend\Entity\AttachmentType;

class AttachmentTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttachmentType::class);
    }

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
