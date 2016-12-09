<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class WelcomeOfferManager
{
    /** @var  EntityManager */
    private $entityManager;
    /** @var  MailerManager */
    private $mailerManager;

    public function __construct(EntityManager $entityManager, MailerManager $mailerManager)
    {
        $this->entityManager = $entityManager;
        $this->mailerManager = $mailerManager;
    }

    public function displayOfferOnHome()
    {
        /** @var \settings $settings */
        $settings = $this->entityManager->getRepository('settings');
        $settings->get('offre-de-bienvenue-sur-home', 'type');
        return (bool) $settings->value;
    }

    /**
     * @param \clients $client
     * @return array
     */
    public function createWelcomeOffer(\clients $client)
    {
        /** @var \offres_bienvenues $welcomeOffer */
        $welcomeOffer = $this->entityManager->getRepository('offres_bienvenues');

        $offerIsValid                    = false;
        $enoughMoneyLeft                 = false;
        $virtualWelcomeOfferTransactions = array(
            \transactions_types::TYPE_WELCOME_OFFER,
            \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION
        );

        if ($welcomeOffer->get(1, 'status = 0 AND id_offre_bienvenue')) {
            /** @var \offres_bienvenues_details $welcomeOfferDetail */
            $welcomeOfferDetail = $this->entityManager->getRepository('offres_bienvenues_details');
            /** @var \transactions $transaction */
            $transaction = $this->entityManager->getRepository('transactions');
            /** @var \wallets_lines $walletLine */
            $walletLine = $this->entityManager->getRepository('wallets_lines');
            /** @var \lenders_accounts $lender */
            $lender = $this->entityManager->getRepository('lenders_accounts');
            /** @var \bank_unilend $unilendBank */
            $unilendBank = $this->entityManager->getRepository('bank_unilend');
            /** @var \settings $setting */
            $setting = $this->entityManager->getRepository('settings');

            $iSumOfAllWelcomeOffersDistributed = $welcomeOfferDetail->sum('type = 0 AND id_offre_bienvenue = ' . $welcomeOffer->id_offre_bienvenue . ' AND status <> 2', 'montant');
            $sumUnilendOffers                  = $transaction->sum('status = ' . \transactions::STATUS_VALID . ' AND type_transaction = ' . \transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER, 'montant');
            $sumTransactionOffers              = $transaction->sum('status = ' . \transactions::STATUS_VALID . ' AND type_transaction IN(' . implode(', ', $virtualWelcomeOfferTransactions) . ')', 'montant');
            $sumAvailableOffers                = $sumUnilendOffers - $sumTransactionOffers;

            $startWelcomeOfferDate = \DateTime::createFromFormat('Y-m-d H:i:s', $welcomeOffer->debut . '00:00:00');
            $endWelcomeOfferDate   = \DateTime::createFromFormat('Y-m-d H:i:s', $welcomeOffer->fin . '23:59:59');
            $today                 = new \DateTime();

            if ($startWelcomeOfferDate <= $today && $endWelcomeOfferDate >= $today) {
                $offerIsValid = true;
            } else {
                $return = ['code' => 1, 'message' => "Il n'y a plus d'offre de bienvenue en cours."];
            }

            if ($iSumOfAllWelcomeOffersDistributed + $welcomeOffer->montant <= $welcomeOffer->montant_limit && $sumAvailableOffers >= $welcomeOffer->montant) {
                $enoughMoneyLeft = true;
            } else {
                $return = ['code' => 2, 'message' => "Il n'y a plus assez d'argent disponible pour créer l'offre de bienvenue."];
            }

            if ($offerIsValid && $enoughMoneyLeft) {
                $setting->get("Offre de bienvenue motif", 'type');
                $lender->get($client->id_client, 'id_client_owner');

                $welcomeOfferDetail->id_offre_bienvenue = $welcomeOffer->id_offre_bienvenue;
                $welcomeOfferDetail->motif              = $setting->value;
                $welcomeOfferDetail->id_client          = $client->id_client;
                $welcomeOfferDetail->montant            = $welcomeOffer->montant;
                $welcomeOfferDetail->status             = \offres_bienvenues_details::STATUS_NEW;
                $welcomeOfferDetail->create();

                $transaction->id_client                 = $client->id_client;
                $transaction->montant                   = $welcomeOffer->montant;
                $transaction->id_offre_bienvenue_detail = $welcomeOfferDetail->id_offre_bienvenue_detail;
                $transaction->id_langue                 = 'fr';
                $transaction->date_transaction          = date('Y-m-d H:i:s');
                $transaction->status                    = \transactions::STATUS_VALID;
                $transaction->type_transaction          = \transactions_types::TYPE_WELCOME_OFFER;
                $transaction->create();

                $walletLine->id_lender                = $lender->id_lender_account;
                $walletLine->type_financial_operation = \wallets_lines::TYPE_MONEY_SUPPLY;
                $walletLine->id_transaction           = $transaction->id_transaction;
                $walletLine->status                   = \wallets_lines::STATUS_VALID;
                $walletLine->type                     = \wallets_lines::PHYSICAL;
                $walletLine->amount                   = $welcomeOffer->montant;
                $walletLine->create();

                $unilendBank->id_transaction = $transaction->id_transaction;
                $unilendBank->montant        = '-' . $welcomeOffer->montant;
                $unilendBank->type           = \bank_unilend::TYPE_UNILEND_WELCOME_OFFER_PATRONAGE;
                $unilendBank->create();

                (0 == $this->mailerManager->sendWelcomeOfferEmail($client, $welcomeOffer)) ? $emailStatus = ' Email non envoyé.' : $emailStatus = ' Email envoyé.';
                $return = ['code' => 0, 'message' => 'Offre de bienvenue créée.' . $emailStatus];
            }
        } else {
            $return = ['code' => 3, 'message' => "Loffre de bienvenue n'existe pas"];
        }
        return $return;
    }

}
