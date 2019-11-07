<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\{RoleableTrait, TimestampableTrait};

/**
 * @ApiResource
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Staff
{
    use RoleableTrait;
    use TimestampableTrait;

    /** @deprecated Just for backward compatibility. Later, we will define a new role list for staff.*/
    public const ROLE_COMPANY_OWNER = 'ROLE_COMPANY_OWNER';

    public const DUTY_STAFF_OPERATOR = 'DUTY_STAFF_OPERATOR';
    public const DUTY_STAFF_MANAGER  = 'DUTY_STAFF_MANAGER';
    public const DUTY_STAFF_ADMIN    = 'DUTY_STAFF_ADMIN';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies", inversedBy="staff")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=false)
     * })
     */
    private $company;

    /**
     * @var Clients
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\Clients", inversedBy="staff")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $client;

    /**
     * @var Collection|MarketSegment[]
     *
     * @ORM\ManyToMany(targetEntity="Unilend\Entity\MarketSegment")
     */
    private $marketSegments;

    /**
     * Staff constructor.
     */
    public function __construct()
    {
        $this->marketSegments = new ArrayCollection();
        $this->added          = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Companies
     */
    public function getCompany(): Companies
    {
        return $this->company;
    }

    /**
     * @param Companies $company
     *
     * @return Staff
     */
    public function setCompany(Companies $company): Staff
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getClient(): Clients
    {
        return $this->client;
    }

    /**
     * @param Clients $client
     *
     * @return Staff
     */
    public function setClient(Clients $client): Staff
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Collection|MarketSegment[]
     */
    public function getMarketSegments()
    {
        return $this->marketSegments;
    }

    /**
     * @param MarketSegment $marketSegment
     *
     * @return Staff
     */
    public function addMarketSegment(MarketSegment $marketSegment): Staff
    {
        if (false === $this->marketSegments->contains($marketSegment)) {
            $this->marketSegments[] = $marketSegment;
        }

        return $this;
    }

    /**
     * @param MarketSegment $marketSegment
     *
     * @return Staff
     */
    public function removeMarketSegment(MarketSegment $marketSegment): Staff
    {
        $this->marketSegments->removeElement($marketSegment);

        return $this;
    }

    /**
     * @param Collection|MarketSegment[] $marketSegments
     *
     * @return Staff
     */
    public function setMarketSegments($marketSegments): Staff
    {
        if (is_array($marketSegments)) {
            $marketSegments = new ArrayCollection($marketSegments);
        }

        $this->marketSegments = $marketSegments;

        return $this;
    }
}
