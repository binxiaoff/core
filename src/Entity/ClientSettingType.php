<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientSettingType
 *
 * @ORM\Table(name="client_setting_type")
 * @ORM\Entity
 */
class ClientSettingType
{
    // @todo migrate to label and remove these constants
    const TYPE_AUTOBID_SWITCH = 1;
    const TYPE_BETA_TESTER    = 2;

    const LABEL_AUTOBID_SWICTH = 'autobid_switch';

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=100, nullable=false, unique=true)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="default_value", type="string", length=191)
     */
    private $defaultValue;

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
     * @ORM\Column(name="id_type", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idType;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return ClientSettingType
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
     * Set defaultValue
     *
     * @param string $defaultValue
     *
     * @return ClientSettingType
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * Get defaultValue
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ClientSettingType
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
     * @return ClientSettingType
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
     * Get idType
     *
     * @return integer
     */
    public function getIdType()
    {
        return $this->idType;
    }
}
