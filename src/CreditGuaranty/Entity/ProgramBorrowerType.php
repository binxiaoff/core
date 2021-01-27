<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

class ProgramBorrowerType
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_program", nullable=false)
     * })
     */
    private Program $program;

    /**
     * @ORM\Column(length=100)
     */
    private string $name;

    /**
     * @param Program $program
     * @param string  $name
     */
    public function __construct(Program $program, string $name)
    {
        $this->program = $program;
        $this->name    = $name;
    }

    /**
     * @return Program
     */
    public function getProgram(): Program
    {
        return $this->program;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return ProgramBorrowerType
     */
    public function setName(string $name): ProgramBorrowerType
    {
        $this->name = $name;

        return $this;
    }
}
