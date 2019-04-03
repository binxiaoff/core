<?php

namespace Unilend\Repository;

use Doctrine\ORM\EntityRepository;

class TranslationsRepository extends EntityRepository
{
    /**
     * @param string $locale
     *
     * @return array
     */
    public function getSections(string $locale = 'fr_FR')
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder
            ->select('DISTINCT(t.section) AS section,
                      COUNT(t.translation) AS count')
            ->where('t.locale = :locale')
            ->setParameter('locale', $locale)
            ->groupBy('t.section')
            ->orderBy('t.section', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $section
     *
     * @return array
     */
    public function getNamesForSection(string $section)
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder
            ->select('DISTINCT(t.name) AS name')
            ->where('t.section = :section')
            ->setParameter('section', $section)
            ->orderBy('t.name', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }
}
