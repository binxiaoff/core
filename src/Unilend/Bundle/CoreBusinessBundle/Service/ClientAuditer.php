<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    ClientDataHistory, Clients, Users, WalletType
};
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class ClientAuditer
{
    const LOGGED_FIELDS = [
        'email',
        'civilite',
        'prenom',
        'nom',
        'nomUsage',
        'mobile',
        'telephone',
        'fonction',
        'idNationalite',
        'naissance',
        'villeNaissance',
        'inseeBirth',
        'idPaysNaissance',
        'fundsOrigin',
        'fundsOriginDetail',
        'optin1',
        'usPerson'
    ];

    /** @var EntityManager */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;

    /**
     * @param EntityManager           $entityManager
     * @param LoggerInterface         $logger
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     */
    public function __construct(EntityManager $entityManager, LoggerInterface $logger, TemplateMessageProvider $messageProvider, \Swift_Mailer $mailer)
    {
        $this->entityManager   = $entityManager;
        $this->logger          = $logger;
        $this->messageProvider = $messageProvider;
        $this->mailer          = $mailer;
    }

    /**
     * @param Clients $client
     * @param Users   $user
     * @param bool    $validateData
     *
     * @return array
     */
    public function logChanges(Clients $client, Users $user, ?bool $validateData = null): array
    {
        $flushEntities = [];
        $classMetaData = $this->entityManager->getClassMetadata(Clients::class);
        $unitOfWork    = $this->entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSet($classMetaData, $client);

        $changeSet     = $unitOfWork->getEntityChangeSet($client);
        $trackedFields = array_intersect(self::LOGGED_FIELDS, array_keys($changeSet));

        foreach ($trackedFields as $fieldName) {
            if ('naissance' === $fieldName) {
                if ($changeSet[$fieldName][0] instanceof \DateTime) {
                    $changeSet[$fieldName][0] = $changeSet[$fieldName][0]->format('d/m/Y');
                }
                if ($changeSet[$fieldName][1] instanceof \DateTime) {
                    $changeSet[$fieldName][1] = $changeSet[$fieldName][1]->format('d/m/Y');
                }
            }

            /**
             * Do not log change when value does not change but type is different
             * Example: idNationalite changing from "int 1" to "string '1'" because setIdNationalite was not called with the right parameter type
             */
            if ($changeSet[$fieldName][0] == $changeSet[$fieldName][1]) {
                continue;
            }

            $clientDataHistory = new ClientDataHistory();
            $clientDataHistory
                ->setIdClient($client)
                ->setField($fieldName)
                ->setOldValue($changeSet[$fieldName][0])
                ->setNewValue($changeSet[$fieldName][1])
                ->setIdUser($user);

            if ($validateData) {
                $clientDataHistory->setDateValidated(new \DateTime());
            }

            $this->entityManager->persist($clientDataHistory);

            $flushEntities[] = $clientDataHistory;

            if ('email' === $clientDataHistory->getField()) {
                $this->notifyEmailChangeToOldAddress($client, $clientDataHistory);
            }
        }

        try {
            $this->entityManager->flush($flushEntities);
        } catch (OptimisticLockException $exception) {
            $this->logger->error('Unable to log client changes: ' . $exception->getMessage(), [
                'id_client' => $client->getIdClient(),
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }

        return array_intersect_key($changeSet, array_flip(self::LOGGED_FIELDS));
    }

    /**
     * @param Clients           $client
     * @param ClientDataHistory $clientDataHistory
     */
    private function notifyEmailChangeToOldAddress(Clients $client, ClientDataHistory $clientDataHistory): void
    {
        $walletRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $wallet           = $walletRepository->getWalletByType($client, WalletType::LENDER);

        $keyWords = [
            'firstName'     => $client->getPrenom(),
            'lastName'      => $client->getNom(),
            'lenderPattern' => $wallet->getWireTransferPattern()
        ];
        $message  = $this->messageProvider->newMessage('alerte-changement-email-preteur', $keyWords);

        try {
            $message->setTo($clientDataHistory->getOldValue());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->error('Could not send email modification alert to the previous lender email. Error: ' . $exception->getMessage(), [
                'id_client'   => $client->getIdClient(),
                'template_id' => $message->getTemplateId(),
                'class'       => __CLASS__,
                'function'    => __FUNCTION__,
                'file'        => $exception->getFile(),
                'line'        => $exception->getLine()
            ]);
        }
    }
}
