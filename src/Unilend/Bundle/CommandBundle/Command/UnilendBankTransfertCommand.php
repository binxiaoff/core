<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class UnilendBankTransfertCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:bank_transfert')
            ->setDescription('Creates virtual transaction for bank transferts');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        $entityManager->getRepository('transactions_types'); //load for constant use

        $jour           = date('d');
        $datesVirements = array(1, 15);

        if (in_array($jour, $datesVirements)) {
            /** @var \platform_account_unilend $oAccountUnilend */
            $oAccountUnilend = $entityManager->getRepository('platform_account_unilend');
            $total           = $oAccountUnilend->getBalance();

            if ($total > 0) {
                /** @var \virements $virements */
                $virements    = $entityManager->getRepository('virements');
                /** @var \transactions $transactions */
                $transactions = $entityManager->getRepository('transactions');
                /** @var \bank_unilend $bank_unilend */
                $bank_unilend = $entityManager->getRepository('bank_unilend');

                $transactions->id_client        = 0;
                $transactions->montant          = $total;
                $transactions->id_langue        = 'fr';
                $transactions->date_transaction = date('Y-m-d H:i:s');
                $transactions->status           = \transactions::STATUS_VALID;
                $transactions->etat             = \transactions::PAYMENT_STATUS_OK;
                $transactions->type_transaction = \transactions_types::TYPE_UNILEND_BANK_TRANSFER;
                $transactions->create();

                $virements->id_client      = 0;
                $virements->id_project     = 0;
                $virements->id_transaction = $transactions->id_transaction;
                $virements->montant        = $total;
                $virements->motif          = 'UNILEND_' . date('dmY');
                $virements->type           = \virements::TYPE_UNILEND;
                $virements->status         = \virements::STATUS_PENDING;
                $virements->create();

                $bank_unilend->id_transaction         = $transactions->id_transaction;
                $bank_unilend->id_echeance_emprunteur = 0;
                $bank_unilend->id_project             = 0;
                $bank_unilend->montant                = '-' . $total;
                $bank_unilend->type                   = \bank_unilend::TYPE_DEBIT_UNILEND;
                $bank_unilend->status                 = 3;
                $bank_unilend->create();

                $oAccountUnilend->id_transaction = $transactions->id_transaction;
                $oAccountUnilend->type           = \platform_account_unilend::TYPE_WITHDRAW;
                $oAccountUnilend->amount         = - $total;
                $oAccountUnilend->create();
            }
        }
    }
}
