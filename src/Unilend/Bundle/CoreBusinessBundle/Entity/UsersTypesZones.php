<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsersTypesZones
 *
 * @ORM\Table(name="users_types_zones", indexes={@ORM\Index(name="id_user_type", columns={"id_user_type"}), @ORM\Index(name="id_zone", columns={"id_zone"})})
 * @ORM\Entity
 */
class UsersTypesZones
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_user_type", type="integer", nullable=false)
     */
    private $idUserType;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_zone", type="integer", nullable=false)
     */
    private $idZone;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user_type_zone", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUserTypeZone;



    /**
     * Set idUserType
     *
     * @param integer $idUserType
     *
     * @return UsersTypesZones
     */
    public function setIdUserType($idUserType)
    {
        $this->idUserType = $idUserType;

        return $this;
    }

    /**
     * Get idUserType
     *
     * @return integer
     */
    public function getIdUserType()
    {
        return $this->idUserType;
    }

    /**
     * Set idZone
     *
     * @param integer $idZone
     *
     * @return UsersTypesZones
     */
    public function setIdZone($idZone)
    {
        $this->idZone = $idZone;

        return $this;
    }

    /**
     * Get idZone
     *
     * @return integer
     */
    public function getIdZone()
    {
        return $this->idZone;
    }

    /**
     * Set added
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
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
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
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idUserTypeZone
     *
     * @return integer
     */
    public function getIdUserTypeZone()
    {
        return $this->idUserTypeZone;
    }
}
