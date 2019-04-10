<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\CompanyRatingHistory;

class CompanyRatingHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyRatingHistory::class);
    }

    /**
     * @param string $siren
     * @param array  $ratingTypes
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getRatingsSirenByDate($siren, array $ratingTypes)
    {
        $ratingTypesSelect = '';
        $ratingTypesJoin   = '';
        foreach ($ratingTypes as $rating) {
            $alias             = 'cr_' . $rating;
            $ratingTypesJoin   .= 'LEFT JOIN company_rating ' . $alias . ' ON crh.id_company_rating_history = ' . $alias . '.id_company_rating_history AND ' . $alias . '.type = "' . $rating . '"';
            $ratingTypesSelect .= $alias . '.value AS ' . $rating . ', ';
        }

        $query =
            'SELECT ' . $ratingTypesSelect . ' DATE(crh.added) AS date
             FROM company_rating_history crh
               INNER JOIN companies co ON co.id_company = crh.id_company
               ' . $ratingTypesJoin . '
             WHERE co.siren = :siren
             GROUP BY crh.id_company_rating_history';

        $statement = $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['siren' => $siren]);
        $result    = $statement->fetchAll();
        $statement->closeCursor();

        return $result;
    }
}
