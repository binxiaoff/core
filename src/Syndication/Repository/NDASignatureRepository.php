<?php

declare(strict_types=1);

namespace Unilend\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Syndication\Entity\NDASignature;

/**
 * @method NDASignature|null find($id, $lockMode = null, $lockVersion = null)
 * @method NDASignature|null findOneBy(array $criteria, array $orderBy = null)
 * @method NDASignature[]    findAll()
 * @method NDASignature[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NDASignatureRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, NDASignature::class);
    }
}
