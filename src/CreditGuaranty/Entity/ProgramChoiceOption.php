<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

/**
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_program_choice_option")
 */
class ProgramChoiceOption
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    public const ACCESS_PATH_BORROWER_TYPE = 'Unilend\CreditGuaranty\Entity\Borrower::type';

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     */
    private Program $program;

    /**
     * @ORM\Column(type="text", length=65535)
     *
     * @Groups({"creditGuaranty:programChoiceOption:read"})
     */
    private string $description;

    /**
     * @ORM\Column(length=100)
     *
     * @Groups({"creditGuaranty:programChoiceOption:read"})
     */
    private string $targetPropertyAccessPath;

    /**
     * @param Program $program
     * @param string  $description
     * @param string  $targetPropertyAccessPath
     */
    public function __construct(Program $program, string $description, string $targetPropertyAccessPath)
    {
        $this->program                  = $program;
        $this->description              = $description;
        $this->targetPropertyAccessPath = $targetPropertyAccessPath;
        $this->added                    = new \DateTimeImmutable();
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
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return ProgramChoiceOption
     */
    public function setDescription(string $description): ProgramChoiceOption
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getTargetPropertyAccessPath(): string
    {
        return $this->targetPropertyAccessPath;
    }
}
