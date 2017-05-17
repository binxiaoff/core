<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PartnerThirdPartyType
 *
 * @ORM\Table(name="partner_third_party_type", uniqueConstraints={@ORM\UniqueConstraint(name="unq_partner_third_party_type_label", columns={"label"})})
 * @ORM\Entity
 */
class PartnerThirdPartyType
{
    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return PartnerThirdPartyType
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
