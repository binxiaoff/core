<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Unilend\Entity\AttachmentType;

/**
 * @method AttachmentType|null find($id, $lockMode = null, $lockVersion = null)
 * @method AttachmentType|null findOneBy(array $criteria, array $orderBy = null)
 * @method AttachmentType[]    findAll()
 * @method AttachmentType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
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
            ->getResult()
        ;
    }
}
