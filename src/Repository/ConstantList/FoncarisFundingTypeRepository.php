<?php

declare(strict_types=1);

namespace Unilend\Repository\ConstantList;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
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
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FoncarisFundingType::class);
    }
}
