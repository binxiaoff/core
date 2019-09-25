<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Unilend\Entity\MailTemplate;

class MailTemplateRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailTemplate::class);
    }

    /**
     * @param string $type
     * @param string $locale
     *
     * @throws NonUniqueResultException
     *
     * @return MailTemplate|null
     */
    public function findMostRecentByTypeAndLocale(string $type, string $locale): ?MailTemplate
    {
        return $this->createQueryBuilder('mt')
            ->where('mt.locale = :locale')
            ->andWhere('mt.type = :type')
            ->orderBy('mt.updated', 'desc')
            ->setMaxResults(1)
            ->setParameters([
                'type'   => $type,
                'locale' => $locale,
            ])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
