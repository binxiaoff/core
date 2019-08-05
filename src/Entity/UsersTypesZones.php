<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsersTypesZones.
 *
 * @ORM\Table(name="users_types_zones", indexes={@ORM\Index(name="id_user_type", columns={"id_user_type"}), @ORM\Index(name="id_zone", columns={"id_zone"}), @ORM\Index(name="id_user_type", columns={"id_user_type"})})
 * @ORM\Entity
 */
class UsersTypesZones
{
    /**
     * @var \Unilend\Entity\UsersTypes
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\UsersTypes")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user_type", referencedColumnName="id_user_type", nullable=false)
     * })
     */
    private $idUserType;

    /**
     * @var \Unilend\Entity\Zones
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Zones")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_zone", referencedColumnName="id_zone", nullable=false)
     * })
     */
    private $idZone;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_user_type_zone", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUserTypeZone;

    /**
     * Set idUserType.
     *
     * @param UsersTypes $idUserType
     *
     * @return UsersTypesZones
     */
    public function setIdUserType(UsersTypes $idUserType)
    {
        $this->idUserType = $idUserType;

        return $this;
    }

    /**
     * Get idUserType.
     *
     * @return UsersTypes
     */
    public function getIdUserType()
    {
        return $this->idUserType;
    }

    /**
     * Set idZone.
     *
     * @param Zones $idZone
     *
     * @return UsersTypesZones
     */
    public function setIdZone(Zones $idZone)
    {
        $this->idZone = $idZone;

        return $this;
    }

    /**
     * Get idZone.
     *
     * @return Zones
     */
    public function getIdZone()
    {
        return $this->idZone;
    }

    /**
     * Set added.
     *
     * @param \DateTime $added
     *
     * @return UsersTypesZones
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added.
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated.
     *
     * @param \DateTime $updated
     *
     * @return UsersTypesZones
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idUserTypeZone.
     *
     * @return int
     */
    public function getIdUserTypeZone()
    {
        return $this->idUserTypeZone;
    }
}
