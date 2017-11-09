<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class CompanyRatingHistoryRepository extends EntityRepository
{
    /**
     * @param array  $ratingTypes
     * @param string $siren
     *
     * @return array
     */
    public function getRatingsSirenByDate($siren, array $ratingTypes)
    {
        $ratingTypesSelect = '';
        $ratingTypesJoin   = '';
        foreach ($ratingTypes as $rating) {
            $alias             = 'cr_' . $rating;
            $ratingTypesJoin   .= 'LEFT JOIN company_rating ' . $alias . ' ON crh.id_company_rating_history = ' . $alias . '.id_company_rating_history AND ' . $alias . '.type = "' . $rating . '"';
            $ratingTypesSelect .= 'IFNULL(null, ' . $alias . '.value) AS ' . $rating . ', ';
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
