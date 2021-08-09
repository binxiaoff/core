<?php

declare(strict_types=1);

namespace KLS\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Syndication\Entity\NDASignature;

/**
 * @method NDASignature|null find($id, $lockMode = null, $lockVersion = null)
 * @method NDASignature|null findOneBy(array $criteria, array $orderBy = null)
 * @method NDASignature[]    findAll()
 * @method NDASignature[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NDASignatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, NDASignature::class);
    }
}
