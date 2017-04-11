<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsersZones
 *
 * @ORM\Table(name="users_zones", uniqueConstraints={@ORM\UniqueConstraint(name="id_user", columns={"id_user", "id_zone"})})
 * @ORM\Entity
 */
class UsersZones
{
    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id_user")
     * })
     */
    private $idUser;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Zones
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Zones")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_zone", referencedColumnName="id_zone")
     * })
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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set idUser
     *
     * @param Users $idUser
     *
     * @return UsersZones
     */
    public function setIdUser(Users $idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return Users
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set idZone
     *
     * @param Zones $idZone
     *
     * @return UsersZones
     */
    public function setIdZone(Zones $idZone)
    {
        $this->idZone = $idZone;

        return $this;
    }

    /**
     * Get idZone
     *
     * @return Zones
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
     * @return UsersZones
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
     * @return UsersZones
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
