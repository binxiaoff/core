<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientAtypicalOperation;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientVigilanceStatusHistory;


class ClientVigilanceStatusManager
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * VigilanceRuleManager constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param Clients                      $client
     * @param                              $vigilanceStatus
     * @param                              $userId
     * @param ClientAtypicalOperation|null $clientAtypicalOperation
     * @param null                         $comment
     * @return null|object|ClientVigilanceStatusHistory
     */
    public function upgradeClientVigilanceStatusHistory(Clients $client, $vigilanceStatus, $userId, ClientAtypicalOperation $clientAtypicalOperation = null, $comment = null)
    {
        $currentClientVigilanceStatus = $this->em->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory')->findOneBy(['client' => $client], ['added' => 'DESC']);

        if (null === $currentClientVigilanceStatus ||
            $currentClientVigilanceStatus instanceof ClientVigilanceStatusHistory &&
            $currentClientVigilanceStatus->getVigilanceStatus() < $vigilanceStatus)
        {
            $vigilanceStatusHistory = new ClientVigilanceStatusHistory();
            $vigilanceStatusHistory->setClient($client)
                ->setVigilanceStatus($vigilanceStatus)
                ->setAtypicalOperation($clientAtypicalOperation)
                ->setIdUser($userId)
                ->setUserComment($comment);
            $this->em->persist($vigilanceStatusHistory);
            $this->em->flush();

            return $vigilanceStatusHistory;
        }

        return $currentClientVigilanceStatus;
    }

    /**
     * @param Clients                      $client
     * @param                              $vigilanceStatus
     * @param                              $userId
     * @param ClientAtypicalOperation|null $clientAtypicalOperation
     * @param null                         $comment
     * @return null|object|ClientVigilanceStatusHistory
     */
    public function retrogradeClientVigilanceStatusHistory(Clients $client, $vigilanceStatus, $userId, ClientAtypicalOperation $clientAtypicalOperation = null, $comment = null)
    {
        $currentClientVigilanceStatus = $this->em->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory')->findOneBy(['client' => $client], ['added' => 'DESC']);

        if (null === $currentClientVigilanceStatus ||
            $currentClientVigilanceStatus instanceof ClientVigilanceStatusHistory
            && $currentClientVigilanceStatus->getVigilanceStatus() >= $vigilanceStatus)
        {
            $vigilanceStatusHistory = new ClientVigilanceStatusHistory();
            $vigilanceStatusHistory->setClient($client)
                ->setVigilanceStatus($vigilanceStatus)
                ->setAtypicalOperation($clientAtypicalOperation)
                ->setIdUser($userId)
                ->setUserComment($comment);
            $this->em->persist($vigilanceStatusHistory);
            $this->em->flush();

            return $vigilanceStatusHistory;
        }

        return $currentClientVigilanceStatus;
    }
}
