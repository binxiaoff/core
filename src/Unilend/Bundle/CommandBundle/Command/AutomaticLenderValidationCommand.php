<?php


namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;
use Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\TaxManager;
use Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager;

class AutomaticLenderValidationCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('unilend:lender:auto_validate')
            ->setDescription('Auto validate lenders having all attachments of type (1, 2, 3) validated by GreenPoint with status 9');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var ClientManager $clientManager */
        $clientManager = $this->getContainer()->get('unilend.service.client_manager');

        try {
            $result = $clientManager->getClientsForAutoValidation();

            foreach ($result as $clientId => $item) {
                $client->get($clientId);
                $this->validateLender($client, $item);
            }
        } catch (\Exception $exception) {
            $logger->error('An exception occurred. Exception message: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }
    }

    /**
     * @param \clients $client
     * @param array $attachment
     */
    private function validateLender(\clients $client, array $attachment)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $entityManager->getRepository('clients_adresses');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $entityManager->getRepository('lenders_accounts');
        /** @var \users_history $userHistory */
        $userHistory = $entityManager->getRepository('users_history');
        /** @var \clients_status_history $clientStatusHistory */
        $clientStatusHistory = $entityManager->getRepository('clients_status_history');
        /** @var WelcomeOfferManager $welcomeOfferManager */
        $welcomeOfferManager = $this->getContainer()->get('unilend.service.welcome_offer_manager');
        /** @var MailerManager $mailerManager */
        $mailerManager = $this->getContainer()->get('unilend.service.email_manager');
        /** @var TaxManager $taxManager */
        $taxManager = $this->getContainer()->get('unilend.service.tax_manager');

        $existingClient = $client->getDuplicates($client->nom, $client->prenom, $client->naissance);
        $existingClient = array_shift($existingClient);

        if (false === empty($existingClient) && $existingClient['id_client'] != $client->id_client) {
            $logger->warning('Processing client id: ' . $client->id_client . ' - Duplicate client found: ' . json_encode($existingClient), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
            return;
        } elseif (0 == $clientStatusHistory->counter('id_client = ' . $client->id_client . ' AND id_client_status = (SELECT cs.id_client_status FROM clients_status cs WHERE cs.status = ' . \clients_status::VALIDATED . ')')) { // On check si on a deja eu le compte validé au moins une fois. si c'est pas le cas on check l'offre
            $response = $welcomeOfferManager->createWelcomeOffer($client);
            $logger->info('id_client : ' . $client->id_client . ' Welcome offer creation result: ' . json_encode($response), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $client->id_client]);
        }
        $lenderAccount->get($client->id_client, 'id_client_owner');
        $clientAddress->get($client->id_client, 'id_client');
        $clientStatusHistory->addStatus(\users::USER_ID_CRON, \clients_status::VALIDATED, $client->id_client, 'Validation automatique basée sur Green Point');
        $serialize = serialize(array('id_client' => $client->id_client, 'attachment_data' => $attachment));
        $userHistory->histo(\users_history::FORM_ID_LENDER, 'validation auto preteur', '0', $serialize);

        /** @var \clients_gestion_notifications $clientNotifications */
        $clientNotifications = $entityManager->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_type_notif $clientNotificationType */
        $clientNotificationType = $entityManager->getRepository('clients_gestion_type_notif');

        if (false == $clientNotifications->select('id_client = ' . $client->id_client)) {
            foreach ($clientNotificationType->select() as $notificationType) {
                $clientNotifications->id_client = $client->id_client;
                $clientNotifications->id_notif  = $notificationType['id_client_gestion_type_notif'];

                if (in_array($notificationType['id_client_gestion_type_notif'], [\clients_gestion_type_notif::TYPE_BID_REJECTED, \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT, \clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT, \clients_gestion_type_notif::TYPE_DEBIT])) {
                    $clientNotifications->immediatement = 1;
                } else {
                    $clientNotifications->immediatement = 0;
                }

                if (in_array($notificationType['id_client_gestion_type_notif'], [\clients_gestion_type_notif::TYPE_NEW_PROJECT, \clients_gestion_type_notif::TYPE_BID_PLACED, \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED, \clients_gestion_type_notif::TYPE_REPAYMENT])) {
                    $clientNotifications->quotidienne = 1;
                } else {
                    $clientNotifications->quotidienne = 0;
                }

                if (in_array($notificationType['id_client_gestion_type_notif'], [\clients_gestion_type_notif::TYPE_NEW_PROJECT, \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED])) {
                    $clientNotifications->hebdomadaire = 1;
                } else {
                    $clientNotifications->hebdomadaire = 0;
                }
                $clientNotifications->mensuelle = 0;
                $clientNotifications->create();
            }
        }

        if ($clientStatusHistory->counter('id_client = ' . $client->id_client . ' AND id_client_status = 5') > 0) {
            $mailerManager->sendClientValidationEmail($client, 'preteur-validation-modification-compte');
        } else {
            $mailerManager->sendClientValidationEmail($client, 'preteur-confirmation-activation');
        }
        $taxManager->addTaxToApply($client, $lenderAccount, $clientAddress, \users::USER_ID_CRON);
    }

}
