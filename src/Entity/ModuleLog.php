<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableAddedOnlyTrait};

/**
 * @ORM\Entity
 */
class ModuleLog
{
    use TimestampableAddedOnlyTrait;
    use BlamableAddedTrait;

    /**
     * @var Module
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Module")
     * @ORM\JoinColumn(nullable=false, name="id_module")
     */
    private $module;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $activated;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @param Module $module
     *
     * @throws Exception
     */
    public function __construct(Module $module)
    {
        $this->module    = $module;
        $this->addedBy   = $module->getUpdatedBy();
        $this->activated = $module->isActivated();
        $this->added     = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isActivated(): bool
    {
        return $this->activated;
    }
}
