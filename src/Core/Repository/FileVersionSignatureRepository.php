<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\FileVersionSignature;

/**
 * @method FileVersionSignature|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileVersionSignature|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileVersionSignature[]    findAll()
 * @method FileVersionSignature[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileVersionSignatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileVersionSignature::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(FileVersionSignature $fileVersionSignature): void
    {
        $this->getEntityManager()->persist($fileVersionSignature);
        $this->getEntityManager()->flush();
    }
}
