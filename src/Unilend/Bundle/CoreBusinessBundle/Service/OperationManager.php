<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Transfer;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

class OperationManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var WalletManager
     */
    private $walletManager;

    public function __construct(EntityManager $entityManager, WalletManager $walletManager)
    {
        $this->entityManager = $entityManager;
        $this->walletManager = $walletManager;
    }

    public function newOperation(
        $amount,
        Wallet $debtor = null,
        Wallet $creditor = null,
        OperationType $type,
        Projects $project = null,
        Loans $loan = null,
        Bids $bid = null,
        EcheanciersEmprunteur $payment = null,
        Echeanciers $repayment = null,
        Backpayline $backPayLine = null,
        OffresBienvenuesDetails $welcomeOffer = null,
        Virements $wireTransferOut = null,
        Receptions $wireTransferIn = null,
        Prelevements $directDebit = null,
        Transfer $transfer = null
    )
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $operation = new Operation();
            $operation->setWalletDebtor($debtor)
                      ->setWalletCreditor($creditor)
                      ->setAmount($amount)
                      ->setType($type)
                      ->setProject($project)
                      ->setLoan($loan)
                      ->setBid($bid)
                      ->setPaymentSchedule($payment)
                      ->setRepaymentSchedule($repayment)
                      ->setBackpayline($backPayLine)
                      ->setWelcomeOffer($welcomeOffer)
                      ->setWireTransferOut($wireTransferOut)
                      ->setWireTransferIn($wireTransferIn)
                      ->setDirectDebit($directDebit)
                      ->setTransfer($transfer);
            $this->entityManager->persist($operation);

            $this->walletManager->handle($operation);

            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }
    }
}
