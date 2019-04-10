<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\TransmissionSequence;

class TransmissionSequenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransmissionSequence::class);
    }

    /**
     * @param string $elementName
     *
     * @return TransmissionSequence
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getNextSequence(string $elementName): TransmissionSequence
    {
        if (null !== $sequence = $this->findOneBy(['elementName' => $elementName])) {
            $sequence->setSequence($sequence->getSequence() + 1);
            $this->getEntityManager()->flush($sequence);
        } else {
            $sequence = new TransmissionSequence();
            $sequence
                ->setElementName($elementName)
                ->setSequence(1);
            $this->getEntityManager()->persist($sequence);
            $this->getEntityManager()->flush($sequence);
        }

        return $sequence;
    }

    /**
     * @param string $elementName
     *
     * @return null|TransmissionSequence
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function rollbackSequence(string $elementName): ?TransmissionSequence
    {
        if (null !== $sequence = $this->findOneBy(['elementName' => $elementName])) {
            $sequence->setSequence($sequence->getSequence() - 1);
            $this->getEntityManager()->flush($sequence);
        }

        return $sequence;
    }
}
