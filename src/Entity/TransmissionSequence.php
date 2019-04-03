<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TransmissionSequence
 *
 * @ORM\Table(name="transmission_sequence", indexes={@ORM\Index(name="element_name", columns={"element_name"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\TransmissionSequenceRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TransmissionSequence
{
    /**
     * @var string
     *
     * @ORM\Column(name="element_name", type="string", length=164)
     */
    private $elementName;

    /**
     * @var int
     *
     * @ORM\Column(name="sequence", type="integer")
     */
    private $sequence;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_transmission_sequence", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTransmissionSequence;

    /**
     * Set elementName
     *
     * @param string $elementName
     *
     * @return TransmissionSequence
     */
    public function setElementName(string $elementName): TransmissionSequence
    {
        $this->elementName = $elementName;

        return $this;
    }

    /**
     * Get elementName
     *
     * @return string
     */
    public function getElementName(): string
    {
        return $this->elementName;
    }

    /**
     * Set sequence
     *
     * @param integer $sequence
     *
     * @return TransmissionSequence
     */
    public function setSequence(int $sequence): TransmissionSequence
    {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Get sequence
     *
     * @return integer
     */
    public function getSequence(): int
    {
        return $this->sequence;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return TransmissionSequence
     */
    public function setAdded(\DateTime $added): TransmissionSequence
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return TransmissionSequence
     */
    public function setUpdated(\DateTime $updated): TransmissionSequence
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * Get idTransmissionSequence
     *
     * @return integer
     */
    public function getIdTransmissionSequence(): int
    {
        return $this->idTransmissionSequence;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function seUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
