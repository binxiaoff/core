<?php

namespace Unilend\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Connection;
use Unilend\Entity\{CompanyRating, CompanyRatingHistory};

class CompanyRatingRepository extends EntityRepository
{
    /**
     * @param CompanyRatingHistory|int $companyRatingHistory
     * @param array                    $ratingTypes
     *
     * @return array
     */
    public function getRatingsByTypeAndHistory($companyRatingHistory, array $ratingTypes): array
    {
        $ratings      = [];
        $queryBuilder = $this->createQueryBuilder('cr');

        $queryBuilder
            ->where('cr.idCompanyRatingHistory = :companyRatingHistory')
            ->andWhere('cr.type IN (:types)')
            ->setParameter('companyRatingHistory', $companyRatingHistory)
            ->setParameter('types', $ratingTypes, Connection::PARAM_STR_ARRAY);

        /** @var CompanyRating $rating */
        foreach ($queryBuilder->getQuery()->getResult() as $rating) {
            $ratings[$rating->getType()] = $rating->getValue();
        }

        return $ratings;
    }
}
