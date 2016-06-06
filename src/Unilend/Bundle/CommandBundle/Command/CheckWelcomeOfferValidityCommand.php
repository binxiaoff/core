<?php

namespace Unilend\Bundle\CommandBundle\Command;


use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Service\Simulator\EntityManager;

class CheckWelcomeOfferValidityCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('check:welcomeOfferValidity')
            ->setDescription('Remove WelcomeOffers not used by lenders during a time period');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \settings $oSettings */
        $oSettings = $entityManager->getRepository('settings');
        /** @var \offres_bienvenues_details $oWelcomeOfferDetails */
        $oWelcomeOfferDetails = $entityManager->getRepository('offres_bienvenues_details');
        /** @var \transactions $oTransactions */
        $oTransactions = $entityManager->getRepository('transactions');
        /** @var \wallets_lines $oWalletsLines */
        $oWalletsLines = $entityManager->getRepository('wallets_lines');
        /** @var \bank_unilend $oBankUnilend */
        $oBankUnilend = $entityManager->getRepository('bank_unilend');
        /** @var \lenders_accounts $oLendersAccounts */
        $oLendersAccounts = $entityManager->getRepository('lenders_accounts');
        /** @var Logger $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');

        $oSettings->get('Durée validité Offre de bienvenue', 'type');
        $sOfferValidity = $oSettings->value;

        $aUnusedWelcomeOffers = $oWelcomeOfferDetails->select('status = 0');
        $oDateTime            = new \DateTime();

        $iNumberOfUnusedWelcomeOffers = 0;

        foreach ($aUnusedWelcomeOffers as $aWelcomeOffer) {
            $oAdded    = \DateTime::createFromFormat('Y-m-d H:i:s', $aWelcomeOffer['added']);
            $oInterval = $oDateTime->diff($oAdded);

            if ($oInterval->days >= $sOfferValidity) {
                $oWelcomeOfferDetails->get($aWelcomeOffer['id_offre_bienvenue_detail']);
                $oWelcomeOfferDetails->status = 2;
                $oWelcomeOfferDetails->update();

                $oTransactions->id_client                 = $aWelcomeOffer['id_client'];
                $oTransactions->montant                   = -$aWelcomeOffer['montant'];
                $oTransactions->id_offre_bienvenue_detail = $aWelcomeOffer['id_offre_bienvenue_detail'];
                $oTransactions->id_langue                 = 'fr';
                $oTransactions->date_transaction          = date('Y-m-d H:i:s');
                $oTransactions->status                    = '1';
                $oTransactions->etat                      = '1';
                $oTransactions->type_transaction          = \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION;
                $oTransactions->transaction               = 2;
                $oTransactions->create();

                $oLendersAccounts->get($aWelcomeOffer['id_client'], 'id_client_owner');

                $oWalletsLines->id_lender                = $oLendersAccounts->id_lender_account;
                $oWalletsLines->type_financial_operation = \wallets_lines::TYPE_MONEY_SUPPLY;
                $oWalletsLines->id_transaction           = $oTransactions->id_transaction;
                $oWalletsLines->status                   = 1;
                $oWalletsLines->type                     = 1;
                $oWalletsLines->amount                   = -$aWelcomeOffer['montant'];
                $oWalletsLines->create();

                $oBankUnilend->id_transaction = $oTransactions->id_transaction;
                $oBankUnilend->montant        = abs($oWelcomeOfferDetails->montant);
                $oBankUnilend->type           = \bank_unilend::TYPE_UNILEND_WELCOME_OFFER_PATRONAGE;
                $oBankUnilend->create();

                $iNumberOfUnusedWelcomeOffers +=1;
            }
        }
        $logger->info('Number of withdrawn welcome offers : ' . $iNumberOfUnusedWelcomeOffers);
    }
}
