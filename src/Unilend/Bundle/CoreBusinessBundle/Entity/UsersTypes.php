<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsersTypes
 *
 * @ORM\Table(name="users_types")
 * @ORM\Entity
 */
class UsersTypes
{
    const TYPE_ADMIN      = 1;
    const TYPE_RISK       = 2;
    const TYPE_COMMERCIAL = 3;
    const TYPE_MARKETING  = 4;
    const TYPE_COMPLIANCE = 5;
    const TYPE_IT         = 6;
    const TYPE_DIRECTION  = 7;
    const TYPE_EXTERNAL   = 8;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191)
     */
    private $label;

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
     * @ORM\Column(name="id_user_type", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUserType;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return UsersTypes
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return UsersTypes
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
     * @return UsersTypes
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
     * Get idUserType
     *
     * @return integer
     */
    public function getIdUserType()
    {
        return $this->idUserType;
    }
}
