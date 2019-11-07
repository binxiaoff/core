<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity(repositoryClass="Unilend\Repository\CaRegionalBankRepository")
 */
class CaRegionalBank
{
    use ConstantsAwareTrait;

    public const FRIENDLY_GROUP_CENTER     = 1;
    public const FRIENDLY_GROUP_NORTH_EAST = 2;
    public const FRIENDLY_GROUP_WEST       = 3;
    public const FRIENDLY_GROUP_SOUTH      = 4;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\Entity\Companies", cascade={"persist"})
     * @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=false)
     */
    private $company;

    /**
     * @ORM\Column(type="integer")
     */
    private $friendlyGroup;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Companies|null
     */
    public function getCompany(): ?Companies
    {
        return $this->company;
    }

    /**
     * @return int|null
     */
    public function getFriendlyGroup(): ?int
    {
        return $this->friendlyGroup;
    }

    /**
     * @return array
     */
    public function getAllFriendlyGroups(): array
    {
        return self::getConstants('FRIENDLY_GROUP_');
    }
}
