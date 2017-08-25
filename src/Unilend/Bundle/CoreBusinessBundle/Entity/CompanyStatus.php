<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyStatus
 *
 * @ORM\Table(name="company_status", uniqueConstraints={@ORM\UniqueConstraint(name="label_UNIQUE", columns={"label"})})
 * @ORM\Entity
 */
class CompanyStatus
{
    const STATUS_IN_BONIS               = 'in_bonis';
    const STATUS_PRECAUTIONARY_PROCESS  = 'precautionary_process';
    const STATUS_RECEIVER_SHIP          = 'receivership';
    const STATUS_COMPULSORY_LIQUIDATION = 'compulsory_liquidation';
    const STATUS_DEFAULT                = 'default';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="smallint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return CompanyStatus
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }
}
