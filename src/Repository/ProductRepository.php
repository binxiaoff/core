<?php

namespace Unilend\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{Clients, ProductAttribute, ProductAttributeType};

class ProductRepository extends EntityRepository
{
    /**
     * @param Clients|int|null $client
     *
     * @return array
     */
    public function findAvailableProductsByClient($client = null)
    {
        if (null === $client) {
            return [];
        }

        if (null !== $client && false === ($client instanceof Clients)) {
            $client = $this->getEntityManager()->getRepository(Clients::class)->find($client);
        }

        $subQueryBuilder = $this->_em->createQueryBuilder();
        $subQueryBuilder
            ->select('IDENTITY (pa.idProduct)')
            ->leftJoin(ProductAttributeType::class, 'pat')
            ->innerJoin(ProductAttribute::class, 'pa', Join::WITH, $subQueryBuilder->expr()->eq('pat.idType', 'pa.idType'))
            ->where($subQueryBuilder->expr()->in('pat.label', [ProductAttributeType::ELIGIBLE_CLIENT_ID, ProductAttributeType::ELIGIBLE_CLIENT_TYPE]))
            ->groupBy('pa.idProduct');

        $queryBuilder = $this->createQueryBuilder('p');

        if (null === $client || empty($client->getType())) {
            $queryBuilder
                ->where($queryBuilder->expr()->notIn('p.idProduct', $subQueryBuilder->getDQL()));
        } else {
            // There may be an issue if a product was configured with lender type AND lender ID restrictions but only one matching given client
            $lenderIdSubQueryBuilder = $this->_em->createQueryBuilder();
            $lenderIdSubQueryBuilder
                ->select('IDENTITY (pa_id.idProduct)')
                ->leftJoin(ProductAttributeType::class, 'pat_id')
                ->innerJoin(ProductAttribute::class, 'pa_id', Join::WITH, $lenderIdSubQueryBuilder->expr()->eq('pat_id.idType', 'pa_id.idType'))
                ->where($lenderIdSubQueryBuilder->expr()->eq('pat_id.label', ':lenderIdAttributeLabel'))
                ->andWhere($lenderIdSubQueryBuilder->expr()->eq('pa_id.attributeValue', $client->getIdClient()))
                ->groupBy('pa_id.idProduct');

            $lenderTypeSubQueryBuilder = $this->_em->createQueryBuilder();
            $lenderTypeSubQueryBuilder
                ->select('IDENTITY (pa_type.idProduct)')
                ->leftJoin(ProductAttributeType::class, 'pat_type')
                ->innerJoin(ProductAttribute::class, 'pa_type', Join::WITH, $lenderTypeSubQueryBuilder->expr()->eq('pat_type.idType', 'pa_type.idType'))
                ->where($lenderTypeSubQueryBuilder->expr()->eq('pat_type.label', ':lenderTypeAttributeLabel'))
                ->andWhere($lenderTypeSubQueryBuilder->expr()->eq('pa_type.attributeValue', $client->getType()))
                ->groupBy('pa_type.idProduct');

            $queryBuilder
                ->where($queryBuilder->expr()->orX(
                    $queryBuilder->expr()->notIn('p.idProduct', $subQueryBuilder->getDQL()),
                    $queryBuilder->expr()->in('p.idProduct', $lenderTypeSubQueryBuilder->getDQL()),
                    $queryBuilder->expr()->in('p.idProduct', $lenderIdSubQueryBuilder->getDQL())
                ))
                ->setParameter('lenderIdAttributeLabel', ProductAttributeType::ELIGIBLE_CLIENT_ID)
                ->setParameter('lenderTypeAttributeLabel', ProductAttributeType::ELIGIBLE_CLIENT_TYPE);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
