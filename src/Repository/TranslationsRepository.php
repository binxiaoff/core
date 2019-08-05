<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\Translations;

/**
 * @method Translations|null find($id, $lockMode = null, $lockVersion = null)
 * @method Translations|null findOneBy(array $criteria, array $orderBy = null)
 * @method Translations[]    findAll()
 * @method Translations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TranslationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Translations::class);
    }

    /**
     * @param string $locale
     *
     * @return array
     */
    public function getSections(string $locale = 'fr_FR'): array
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder
            ->select('DISTINCT(t.section) AS section,
                      COUNT(t.translation) AS count')
            ->where('t.locale = :locale')
            ->setParameter('locale', $locale)
            ->groupBy('t.section')
            ->orderBy('t.section', 'ASC')
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $section
     *
     * @return array
     */
    public function getNamesForSection(string $section): array
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder
            ->select('DISTINCT(t.name) AS name')
            ->where('t.section = :section')
            ->setParameter('section', $section)
            ->orderBy('t.name', 'ASC')
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}
