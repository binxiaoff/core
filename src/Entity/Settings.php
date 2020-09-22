<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
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
     * Settings constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->added = new DateTimeImmutable();
    }

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
