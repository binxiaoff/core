<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Settings
 *
 * @ORM\Table(name="settings", uniqueConstraints={@ORM\UniqueConstraint(name="type", columns={"type", "id_template"})})
 * @ORM\Entity
 */
class Settings
{
    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=191, nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_template", type="integer", nullable=false)
     */
    private $idTemplate;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", length=16777215, nullable=false)
     */
    private $value;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="cms", type="string", length=50, nullable=false)
     */
    private $cms;

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
     * @ORM\Column(name="id_setting", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idSetting;



    /**
     * Set type
     *
     * @param string $type
     *
     * @return Settings
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set idTemplate
     *
     * @param integer $idTemplate
     *
     * @return Settings
     */
    public function setIdTemplate($idTemplate)
    {
        $this->idTemplate = $idTemplate;

        return $this;
    }

    /**
     * Get idTemplate
     *
     * @return integer
     */
    public function getIdTemplate()
    {
        return $this->idTemplate;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return Settings
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return Settings
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set cms
     *
     * @param string $cms
     *
     * @return Settings
     */
    public function setCms($cms)
    {
        $this->cms = $cms;

        return $this;
    }

    /**
     * Get cms
     *
     * @return string
     */
    public function getCms()
    {
        return $this->cms;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Settings
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
     * @return Settings
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
     * Get idSetting
     *
     * @return integer
     */
    public function getIdSetting()
    {
        return $this->idSetting;
    }
}
