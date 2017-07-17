<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;

class ProductRepository extends EntityRepository
{
    /**
     * @param Clients|int|null $client
     *
     * @return array
     */
    public function findAvailableProductsByClient($client = null)
    {
        if (null !== $client && false === ($client instanceof Clients)) {
            $client = $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:Clients')->find($client);
        }

        $subQueryBuilder = $this->_em->createQueryBuilder();
        $subQueryBuilder
            ->select('IDENTITY (pa.idProduct)')
            ->from('UnilendCoreBusinessBundle:ProductAttributeType', 'pat')
            ->innerJoin('UnilendCoreBusinessBundle:ProductAttribute', 'pa', Join::WITH, $subQueryBuilder->expr()->eq('pat.idType', 'pa.idType'))
            ->where($subQueryBuilder->expr()->in('pat.label', [ProductAttributeType::ELIGIBLE_CLIENT_ID, ProductAttributeType::ELIGIBLE_CLIENT_TYPE]))
            ->groupBy('pa.idProduct');

        $queryBuilder = $this->createQueryBuilder('p');

        if (null === $client) {
            $queryBuilder
                ->where($queryBuilder->expr()->notIn('p.idProduct', $subQueryBuilder->getDQL()));
        } else {
            // There may be an issue if a product was configured with lender type AND lender ID restrictions but only one matching given client
            $lenderIdSubQueryBuilder = $this->_em->createQueryBuilder();
            $lenderIdSubQueryBuilder
                ->select('IDENTITY (pa_id.idProduct)')
                ->from('UnilendCoreBusinessBundle:ProductAttributeType', 'pat_id')
                ->innerJoin('UnilendCoreBusinessBundle:ProductAttribute', 'pa_id', Join::WITH, $lenderIdSubQueryBuilder->expr()->eq('pat_id.idType', 'pa_id.idType'))
                ->where($lenderIdSubQueryBuilder->expr()->eq('pat_id.label', ':lenderIdAttributeLabel'))
                ->andWhere($lenderIdSubQueryBuilder->expr()->eq('pa_id.attributeValue', $client->getIdClient()))
                ->groupBy('pa_id.idProduct');

            $lenderTypeSubQueryBuilder = $this->_em->createQueryBuilder();
            $lenderTypeSubQueryBuilder
                ->select('IDENTITY (pa_type.idProduct)')
                ->from('UnilendCoreBusinessBundle:ProductAttributeType', 'pat_type')
                ->innerJoin('UnilendCoreBusinessBundle:ProductAttribute', 'pa_type', Join::WITH, $lenderTypeSubQueryBuilder->expr()->eq('pat_type.idType', 'pa_type.idType'))
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
