<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SepaRejectionReason
 *
 * @ORM\Table(name="sepa_rejection_reason")
 * @ORM\Entity
 */

class SepaRejectionReason
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    var $id;

    /**
     * @var string
     *
     * @ORM\Column(name="iso_code", type="string", length=4, nullable=false)
     */
    var $isoCode;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    var $label;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $isoCode
     *
     * @return SepaRejectionReason
     */
    public function setIsoCode($isoCode)
    {
        $this->isoCode = $isoCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getIsoCode()
    {
        return $this->isoCode;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return SepaRejectionReason
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }
}
