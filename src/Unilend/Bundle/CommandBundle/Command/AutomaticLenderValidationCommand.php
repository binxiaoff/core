<?php


namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager;
use Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;
use Unilend\Bundle\CoreBusinessBundle\Service\NotificationManager;
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

        try {
            $result = $this->getClientsForAutoValidation();

            foreach ($result as $clientId => $item) {
                $client->get($clientId);
                $this->validateLender($client, $item);
            }
        } catch (\Exception $exception) {
            $logger->error('An exception occurred. Exception message: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }
    }

    /**
     * @return array
     */
    private function getClientsForAutoValidation()
    {
        $clientStatus   = [\clients_status::TO_BE_CHECKED, \clients_status::COMPLETENESS_REPLY, \clients_status::MODIFICATION];
        $attachmentType = [\attachment_type::CNI_PASSPORTE, \attachment_type::JUSTIFICATIF_DOMICILE, \attachment_type::RIB];
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');

        $result            = $client->getClientsToAutoValidate($clientStatus, $attachmentType);
        $clientsToValidate = [];

        if (false === empty($result)) {
            foreach ($result as $row) {
                $clientsToValidate[$row['id_client']][$row['id_type']] = [
                    'revalidate'   => $row['revalidate'],
                    'final_status' => $row['final_status']
                ];
            }
            unset($result);

            foreach ($clientsToValidate as $clientId => $attachments) {
                $attachmentTypesFound = array_keys($attachments);

                foreach ($attachmentType as $id) {
                    // Check if all required attachments are present
                    if (false === in_array($id, $attachmentTypesFound)) {
                        unset($clientsToValidate[$clientId]);
                        continue 2;
                    }
                }

                foreach ($attachments as $attachment) {
                    // Check if it is the final status and that no revalidation is required
                    if (\greenpoint_attachment::REVALIDATE_YES == $attachment['revalidate'] || \greenpoint_attachment::FINAL_STATUS_NO == $attachment['final_status']) {
                        unset($clientsToValidate[$clientId]);
                        continue 2;
                    }
                }
            }
        }
        return $clientsToValidate;
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
        /** @var ClientStatusManager $clientStatusManager */
        $clientStatusManager = $this->getContainer()->get('unilend.service.client_status_manager');

        $existingClient = $client->getDuplicates($client->nom, $client->prenom, $client->naissance);
        $existingClient = array_shift($existingClient);

        if (false === empty($existingClient) && $existingClient['id_client'] != $client->id_client) {
            $logger->warning('Processing client id: ' . $client->id_client . ' - Duplicate client found: ' . json_encode($existingClient), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
            return;
        } elseif (1 == $client->origine && 0 == $clientStatusHistory->counter('id_client = ' . $client->id_client . ' AND id_client_status = (SELECT cs.id_client_status FROM clients_status cs WHERE cs.status = ' . \clients_status::VALIDATED . ')')) {
            $response = $welcomeOfferManager->createWelcomeOffer($client);
            $logger->info('Client ID: ' . $client->id_client . ' Welcome offer creation result: ' . json_encode($response), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $client->id_client]);
        }
        $lenderAccount->get($client->id_client, 'id_client_owner');
        $clientAddress->get($client->id_client, 'id_client');
        $clientStatusManager->addClientStatus($client, \users::USER_ID_CRON, \clients_status::VALIDATED, 'Validation automatique basée sur Green Point');

        $serialize = serialize(array('id_client' => $client->id_client, 'attachment_data' => $attachment));
        $userHistory->histo(\users_history::FORM_ID_LENDER, 'validation auto preteur', '0', $serialize);

        if ($clientStatusHistory->counter('id_client = ' . $client->id_client . ' AND id_client_status = 5') > 0) {
            $mailerManager->sendClientValidationEmail($client, 'preteur-validation-modification-compte');
        } else {
            $mailerManager->sendClientValidationEmail($client, 'preteur-confirmation-activation');
        }
        $taxManager->addTaxToApply($client, $lenderAccount, $clientAddress, \users::USER_ID_CRON);
    }

}
