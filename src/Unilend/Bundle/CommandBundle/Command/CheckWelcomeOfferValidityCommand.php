<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class CheckWelcomeOfferValidityCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('check:welcome_offer_validity')
            ->setDescription('Remove WelcomeOffers not used by lenders during a time period');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        /** @var \offres_bienvenues_details $welcomeOfferDetails */
        $welcomeOfferDetails = $entityManager->getRepository('offres_bienvenues_details');
        /** @var \transactions $transactions */
        $transactions = $entityManager->getRepository('transactions');
        /** @var \wallets_lines $walletsLines */
        $walletsLines = $entityManager->getRepository('wallets_lines');
        /** @var \bank_unilend $bankUnilend */
        $bankUnilend = $entityManager->getRepository('bank_unilend');
        /** @var \lenders_accounts $lendersAccounts */
        $lendersAccounts = $entityManager->getRepository('lenders_accounts');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');

        $settings->get('Durée validité Offre de bienvenue', 'type');
        $offerValidity               = $settings->value;
        $dateLimit                   = new \DateTime('NOW - ' . $offerValidity . ' DAYS');
        $numberOfUnusedWelcomeOffers = 0;

        foreach ($welcomeOfferDetails->getUnusedWelcomeOffers($dateLimit) as $welcomeOffer) {
            if ($lendersAccounts->get($welcomeOffer['id_client'], 'id_client_owner')){
                $accountBalance = $transactions->getSolde($lendersAccounts->id_client_owner);

                if (0 > bccomp($accountBalance, bcdiv($welcomeOffer['montant'], 100, 2), 2)){
                    $logger->info('Balance of ' . $accountBalance . ' is insufficient to withdraw unused welcome offer for client : ' . $lendersAccounts->id_client_owner);
                } else {
                    $welcomeOfferDetails->get($welcomeOffer['id_offre_bienvenue_detail']);
                    $welcomeOfferDetails->status = \offres_bienvenues_details::STATUS_CANCELED;
                    $welcomeOfferDetails->update();

                    $transactions->id_client                 = $welcomeOffer['id_client'];
                    $transactions->montant                   = -$welcomeOffer['montant'];
                    $transactions->id_offre_bienvenue_detail = $welcomeOffer['id_offre_bienvenue_detail'];
                    $transactions->id_langue                 = 'fr';
                    $transactions->date_transaction          = date('Y-m-d H:i:s');
                    $transactions->status                    = \transactions::STATUS_VALID;
                    $transactions->type_transaction          = \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION;
                    $transactions->create();

                    $walletsLines->id_lender                = $lendersAccounts->id_lender_account;
                    $walletsLines->type_financial_operation = \wallets_lines::TYPE_MONEY_SUPPLY;
                    $walletsLines->id_transaction           = $transactions->id_transaction;
                    $walletsLines->status                   = 1;
                    $walletsLines->type                     = 1;
                    $walletsLines->amount                   = -$welcomeOffer['montant'];
                    $walletsLines->create();

                    $bankUnilend->id_transaction = $transactions->id_transaction;
                    $bankUnilend->montant        = abs($welcomeOfferDetails->montant);
                    $bankUnilend->type           = \bank_unilend::TYPE_UNILEND_WELCOME_OFFER_PATRONAGE;
                    $bankUnilend->create();

                    $numberOfUnusedWelcomeOffers +=1;
                }
            }
        }

        $logger->info('Number of withdrawn welcome offers: ' . $numberOfUnusedWelcomeOffers);
    }
}
