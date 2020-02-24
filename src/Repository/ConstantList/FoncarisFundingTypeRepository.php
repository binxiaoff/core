<?php

declare(strict_types=1);

namespace Unilend\Repository\ConstantList;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\ConstantList\FoncarisFundingType;

/**
 * @method FoncarisFundingType|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoncarisFundingType|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoncarisFundingType[]    findAll()
 * @method FoncarisFundingType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoncarisFundingTypeRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoncarisFundingType::class);
    }
}
