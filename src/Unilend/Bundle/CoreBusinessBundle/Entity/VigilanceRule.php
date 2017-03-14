<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VigilanceRule
 *
 * @ORM\Table(name="vigilance_rule", uniqueConstraints={@ORM\UniqueConstraint(name="label", columns={"label"})})
 * @ORM\Entity
 */
class VigilanceRule
{
    const VIGILANCE_STATUS_LOW    = 0;
    const VIGILANCE_STATUS_MEDIUM = 1;
    const VIGILANCE_STATUS_HIGH   = 2;
    const VIGILANCE_STATUS_REFUSE = 3;

    public static $vigilanceStatusColor = [
        self::VIGILANCE_STATUS_LOW    => 'green',
        self::VIGILANCE_STATUS_MEDIUM => 'yellow',
        self::VIGILANCE_STATUS_HIGH   => 'orange',
        self::VIGILANCE_STATUS_REFUSE => 'red'
    ];

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="vigilance_status", type="integer", nullable=false)
     */
    private $vigilanceStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

    /**
     * @var integer
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
