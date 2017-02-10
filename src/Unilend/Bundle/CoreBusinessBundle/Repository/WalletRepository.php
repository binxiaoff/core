<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class WalletRepository extends EntityRepository
{

    /**
     * @return array Wallet[]
     */
    public function getTaxWallets()
    {
        $cb = $this->createQueryBuilder('w');
        $cb->select('w')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->where('wt.label IN (:taxWallets)')
            ->setParameter(
                'taxWallets', [
                    WalletType::TAX_RETENUES_A_LA_SOURCE,
                    WalletType::TAX_CONTRIBUTIONS_ADDITIONNELLES,
                    WalletType::TAX_CRDS,
                    WalletType::TAX_CSG,
                    WalletType::TAX_PRELEVEMENTS_DE_SOLIDARITE,
                    WalletType::TAX_PRELEVEMENTS_OBLIGATOIRES,
                    WalletType::TAX_PRELEVEMENTS_SOCIAUX], Connection::PARAM_INT_ARRAY);
        $query = $cb->getQuery();

        return $query->getResult();
    }

    /**
     * @param integer|Clients   $idClient
     * @param string|WalletType $walletType
     *
     * @return Clients|null
     */
    public function getWalletByType($idClient, $walletType)
    {
        if ($idClient instanceof Clients) {
            $idClient = $idClient->getIdClient();
        }

        if ($walletType instanceof WalletType) {
            $walletType = $walletType->getLabel();
        }

        $cb = $this->createQueryBuilder('w');
        $cb->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->where('w.idClient = :idClient')
            ->andWhere('wt.label = :walletType')
            ->setMaxResults(1)
            ->setParameters(['idClient' => $idClient, 'walletType' => $walletType]);
        $query = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

}
