<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

/**
 * Class RecoveryManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class RecoveryManager
{
    /**
     * 0.844 is the rate for getting the total amount including the MCS commission and tax.
     * TODO : replace it when doing the Recovery project
     */
    const MCS_COMMISSION_AND_TAX = 0.844;
    const RECOVERY_TAX_DATE_CHANGE = '2016-04-19';

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager, LoggerInterface $logger)
    {
        $this->logger        = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * @param $recoveryTaxExcl
     * @return string
     */
    public function getAmountWithRecoveryTax($recoveryTaxExcl)
    {
        $recoveryTaxIncl = bcdiv($recoveryTaxExcl, self::MCS_COMMISSION_AND_TAX, 5);
        return $recoveryTaxIncl;
    }
}
