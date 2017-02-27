<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TransmissionSequence
 *
 * @ORM\Table(name="transmission_sequence", indexes={@ORM\Index(name="element_name", columns={"element_name"})})
 * @ORM\Entity
 */
class TransmissionSequence
{
    /**
     * @var string
     *
     * @ORM\Column(name="element_name", type="string", length=164, nullable=false)
     */
    private $elementName;

    /**
     * @var integer
     *
     * @ORM\Column(name="sequence", type="integer", nullable=false)
     */
    private $sequence;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
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
    public function setElementName($elementName)
    {
        $this->elementName = $elementName;

        return $this;
    }

    /**
     * Get elementName
     *
     * @return string
     */
    public function getElementName()
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
    public function setSequence($sequence)
    {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Get sequence
     *
     * @return integer
     */
    public function getSequence()
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
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
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
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idTransmissionSequence
     *
     * @return integer
     */
    public function getIdTransmissionSequence()
    {
        return $this->idTransmissionSequence;
    }
}
