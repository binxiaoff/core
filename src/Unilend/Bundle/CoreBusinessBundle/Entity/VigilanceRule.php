<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VigilanceRule
 *
 * @ORM\Table(name="vigilance_rule")
 * @ORM\Entity
 */
class VigilanceRule
{
    const VIGILANCE_STATUS_LOW    = 0;
    const VIGILANCE_STATUS_MEDIUM = 1;
    const VIGILANCE_STATUS_HIGH   = 2;
    const VIGILANCE_STATUS_REFUSE = 3;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, unique=true)
     */
    private $label;

    /**
     * @var int
     *
     * @ORM\Column(name="vigilance_status", type="smallint")
     */
    private $vigilanceStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_user", type="integer")
     */
    private $idUser;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Set name
     *
     * @param string $name
     *
     * @return VigilanceRule
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return VigilanceRule
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
     * Set vigilanceStatus
     *
     * @param int $vigilanceStatus
     *
     * @return VigilanceRule
     */
    public function setVigilanceStatus($vigilanceStatus)
    {
        $this->vigilanceStatus = $vigilanceStatus;

        return $this;
    }

    /**
     * Get vigilanceStatus
     *
     * @return int
     */
    public function getVigilanceStatus()
    {
        return $this->vigilanceStatus;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return VigilanceRule
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
     * @return VigilanceRule
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
     * Set idUser
     *
     * @param integer $idUser
     *
     * @return VigilanceRule
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return integer
     */
    public function getIdUser()
    {
        return $this->idUser;
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
