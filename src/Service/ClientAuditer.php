<?php

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Unilend\Entity\{ClientDataHistory, Clients, Users};

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

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
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
}
