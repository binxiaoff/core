<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyStatus.
 *
 * @ORM\Table(name="company_status")
 * @ORM\Entity
 */
class CompanyStatus
{
    public const STATUS_IN_BONIS               = 'in_bonis';
    public const STATUS_PRECAUTIONARY_PROCESS  = 'precautionary_process';
    public const STATUS_RECEIVERSHIP           = 'receivership';
    public const STATUS_COMPULSORY_LIQUIDATION = 'compulsory_liquidation';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="smallint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, unique=true)
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
