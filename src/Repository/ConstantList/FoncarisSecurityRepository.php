<?php

declare(strict_types=1);

namespace Unilend\Repository\ConstantList;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\ConstantList\FoncarisSecurity;

/**
 * @method FoncarisSecurity|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoncarisSecurity|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoncarisSecurity[]    findAll()
 * @method FoncarisSecurity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoncarisSecurityRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoncarisSecurity::class);
    }
}
