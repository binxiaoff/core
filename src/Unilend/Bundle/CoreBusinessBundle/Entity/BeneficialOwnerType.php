<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BeneficialOwnerType
 *
 * @ORM\Table(name="beneficial_owner_type")
 * @ORM\Entity
 */
class BeneficialOwnerType
{
    const TYPE_LEGAL_MANAGER = 'legal_manager';
    const TYPE_SHAREHOLDER   = 'shareholder';

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false, unique=true)
     */
    private $label;

    /**
     * @var int
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
     * @return BeneficialOwnerType
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
