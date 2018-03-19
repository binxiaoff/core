<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\TransmissionSequence;

class TransmissionSequenceRepository extends EntityRepository
{
    /**
     * @param string $elementName
     *
     * @return TransmissionSequence
     *
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
    public function resetToPreviousSequence(string $elementName): ?TransmissionSequence
    {
        if (null !== $sequence = $this->findOneBy(['elementName' => $elementName])) {
            $sequence->setSequence($sequence->getSequence() - 1);
            $this->getEntityManager()->flush($sequence);
        }

        return $sequence;
    }
}
