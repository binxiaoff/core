<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientAtypicalOperation;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientVigilanceStatusHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule;


class ClientVigilanceStatusManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * VigilanceRuleManager constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Clients                      $client
     * @param int                          $vigilanceStatus
     * @param int                          $userId
     * @param ClientAtypicalOperation|null $clientAtypicalOperation
     * @param null|string                  $comment
     *
     * @return null|ClientVigilanceStatusHistory
     */
    public function upgradeClientVigilanceStatusHistory(Clients $client, $vigilanceStatus, $userId, ClientAtypicalOperation $clientAtypicalOperation = null, $comment = null)
    {
        $currentClientVigilanceStatus = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory')->findOneBy(['client' => $client], ['added' => 'DESC']);

        if (null === $currentClientVigilanceStatus ||
            $currentClientVigilanceStatus->getVigilanceStatus() < $vigilanceStatus
        ) {
            $vigilanceStatusHistory = new ClientVigilanceStatusHistory();
            $vigilanceStatusHistory->setClient($client)
                ->setVigilanceStatus($vigilanceStatus)
                ->setAtypicalOperation($clientAtypicalOperation)
                ->setIdUser($this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($userId))
                ->setUserComment($comment);
            $this->entityManager->persist($vigilanceStatusHistory);
            $this->entityManager->flush($vigilanceStatusHistory);

            return $vigilanceStatusHistory;
        }

        return $currentClientVigilanceStatus;
    }

    /**
     * @param Clients                      $client
     * @param int                          $vigilanceStatus
     * @param int                          $userId
     * @param ClientAtypicalOperation|null $clientAtypicalOperation
     * @param null|string                  $comment
     *
     * @return null|ClientVigilanceStatusHistory
     */
    public function retrogradeClientVigilanceStatusHistory(Clients $client, $vigilanceStatus, $userId, ClientAtypicalOperation $clientAtypicalOperation = null, $comment = null)
    {
        $currentClientVigilanceStatus = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory')->findOneBy(['client' => $client], ['added' => 'DESC']);

        if (null === $currentClientVigilanceStatus ||
            $currentClientVigilanceStatus->getVigilanceStatus() >= $vigilanceStatus
        ) {
            $vigilanceStatusHistory = new ClientVigilanceStatusHistory();
            $vigilanceStatusHistory->setClient($client)
                ->setVigilanceStatus($vigilanceStatus)
                ->setAtypicalOperation($clientAtypicalOperation)
                ->setIdUser($this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($userId))
                ->setUserComment($comment);
            $this->entityManager->persist($vigilanceStatusHistory);
            $this->entityManager->flush($vigilanceStatusHistory);

            return $vigilanceStatusHistory;
        }

        return $currentClientVigilanceStatus;
    }

    /**
     * @param VigilanceRule $vigilanceRule
     * @param Clients       $client
     * @param null|string   $atypicalValue
     * @param null|string   $operationLog
     * @param null|string   $comment
     * @param boolean       $checkPendingDuplicate
     *
     * @return ClientAtypicalOperation
     */
    public function addClientAtypicalOperation(VigilanceRule $vigilanceRule, Clients $client, $atypicalValue = null, $operationLog = null, $comment = null, $checkPendingDuplicate = false)
    {
        if (true === $checkPendingDuplicate) {
            $pendingAtypicalOperation = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')
                ->findOneBy(['client' => $client, 'rule' => $vigilanceRule, 'atypicalValue' => $atypicalValue, 'operationLog' => $operationLog]);

            if (null !== $pendingAtypicalOperation) {
                return $pendingAtypicalOperation;
            }
        }

        $atypicalOperation = new ClientAtypicalOperation();
        $atypicalOperation->setClient($client)
            ->setRule($vigilanceRule)
            ->setDetectionStatus(ClientAtypicalOperation::STATUS_PENDING)
            ->setAtypicalValue($atypicalValue)
            ->setOperationLog($operationLog)
            ->setUserComment($comment)
            ->setIdUser($this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_CRON));

        $this->entityManager->persist($atypicalOperation);
        $this->entityManager->flush($atypicalOperation);

        return $atypicalOperation;
    }
}
