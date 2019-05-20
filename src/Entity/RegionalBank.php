<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity(repositoryClass="Unilend\Repository\RegionalBankRepository")
 */
class RegionalBank
{
    use TimestampableTrait;
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
     * @ORM\OneToOne(targetEntity="Unilend\Entity\Companies", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_company", referencedColumnName="id_company", nullable=false)
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
     * @param Companies $company
     *
     * @return RegionalBank
     */
    public function setCompany(Companies $company): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFriendlyGroup(): ?int
    {
        return $this->friendlyGroup;
    }

    /**
     * @param int $friendlyGroup
     *
     * @return RegionalBank
     */
    public function setFriendlyGroup(int $friendlyGroup): self
    {
        if (in_array($friendlyGroup, $this->getAllFriendlyGroups())) {
            $this->friendlyGroup = $friendlyGroup;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getAllFriendlyGroups(): array
    {
        return self::getConstants('FRIENDLY_GROUP_');
    }
}
