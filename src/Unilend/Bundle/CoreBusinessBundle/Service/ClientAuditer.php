<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    ClientDataHistory, Clients, Users
};

class ClientAuditer
{
    const LOGGED_FIELDS = [
        'email',
        'civilite',
        'prenom',
        'nom',
        'nom_usage',
        'mobile',
        'telephone',
        'fonction',
        'id_nationalite',
        'naissance',
        'ville_naissance',
        'insee_birth',
        'id_pays_naissance',
        'funds_origin',
        'funds_origin_detail'
    ];

    /** @var EntityManager */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManager   $entityManager
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
    }

    /**
     * @param Clients $client
     * @param Users   $user
     */
    public function logChanges(Clients $client, Users $user): void
    {
        $classMetaData = $this->entityManager->getClassMetadata(Clients::class);
        $unitOfWork    = $this->entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSet($classMetaData, $client);

        $changeSet     = $unitOfWork->getEntityChangeSet($client);
        $trackedFields = array_intersect(self::LOGGED_FIELDS, array_keys($changeSet));

        foreach ($trackedFields as $fieldName) {
            $clientDataHistory = new ClientDataHistory();
            $clientDataHistory
                ->setIdClient($client)
                ->setField($fieldName)
                ->setOldValue($changeSet[$fieldName][0])
                ->setNewValue($changeSet[$fieldName][1])
                ->setIdUser($user);

            $this->entityManager->persist($clientDataHistory);
        }

        try {
            $this->entityManager->flush();
        } catch (OptimisticLockException $exception) {
            $this->logger->error('Unable to log client changes: ' . $exception->getMessage(), [
                'id_client' => $client->getIdClient(),
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }
    }
}
