<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\FoncarisRequest;

/**
 * @method FoncarisRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoncarisRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoncarisRequest[]    findAll()
 * @method FoncarisRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoncarisRequestRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoncarisRequest::class);
    }

    /**
     * @param FoncarisRequest $foncarisRequest
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(FoncarisRequest $foncarisRequest)
    {
        $this->getEntityManager()->persist($foncarisRequest);
        $this->getEntityManager()->flush();
    }
}
