<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class LenderValidator
{
    use Checker\LenderChecker;

    /** @var ProductAttributeManager */
    private $productAttributeManager;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;

    /**
     * LenderValidator constructor.
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManagerSimulator  $entityManagerSimulator
     * @param EntityManager           $entityManager
     */
    public function __construct(
        ProductAttributeManager $productAttributeManager,
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager
    ) {
        $this->productAttributeManager = $productAttributeManager;
        $this->entityManagerSimulator  = $entityManagerSimulator;
        $this->entityManager           = $entityManager;
    }

    /**
     * @param Clients   $client
     * @param \projects $project
     *
     * @return array
     */
    public function isEligible(Clients $client, \projects $project)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is no Lender');
        }

        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        if (null === $wallet) {
            throw new \Exception('Client ' . $client->getIdClient() . ' has no lender wallet');
        }

        $eligible = true;
        $reason = [];
        /** @var \product $product */
        $product = $this->entityManager->getRepository('product');
        if (false === $product->get($project->id_product)) {
            throw new \InvalidArgumentException('The product id ' . $project->id_product . ' does not exist');
        }

        if (false === $this->isLenderEligibleForType($client, $product, $this->productAttributeManager)) {
            $reason[] = \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE;
            $eligible = false;
        }

        if (false === $this->isLenderEligibleForMaxTotalAmount($wallet, $project, $this->productAttributeManager, $this->entityManagerSimulator)) {
            $reason[] = \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
            $eligible = false;
        }

        return [
            'reason'   => $reason,
            'eligible' => $eligible
        ];
    }
}
