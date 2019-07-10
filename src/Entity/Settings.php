<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Table(name="settings")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Settings
{
    use TimestampableTrait;

    public const TYPE_AUTOBID_GLOBAL_SWITCH = 'Auto-bid global switch';
    public const TYPE_AUTOBID_STEP          = 'Auto-bid step';
    public const TYPE_SERVICE_TERMS_PAGE_ID = 'SERVICE_TERMS_PAGE_ID';

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=191, unique=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", length=16777215)
     */
    private $value;

    /**
     * @var int
     *
     * @ORM\Column(name="id_setting", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idSetting;

    /**
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
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
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function getIdSetting()
    {
        return $this->idSetting;
    }
}
