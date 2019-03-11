<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Bundle\CoreBusinessBundle\Entity\Traits\{Roleable, Timestampable};

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Staff
{
    use Roleable;
    use Timestampable;

    /**
     * @deprecated Just for backward compatibility. Later, we will define a new role list for staff.
     */
    const STAFF_ROLE_OWNER = 'STAFF_ROLE_OWNER';

    const ALL_ROLES = [self::STAFF_ROLE_OWNER];

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
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Companies", inversedBy="staff")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company", nullable=false)
     * })
     */
    private $company;

    /**
     * @var Clients
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients", inversedBy="staff")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $client;

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
}
