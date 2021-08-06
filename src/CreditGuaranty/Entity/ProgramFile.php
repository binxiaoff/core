<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Core\Traits\ConstantsAwareTrait;

class ProgramFile
{
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;
    use ConstantsAwareTrait;
    use PublicizeIdentityTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     */
    private Program $program;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\File")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     */
    private File $file;

    /**
     * @ORM\Column(length=60)
     *
     * @Assert\Choice(callback="getTypes")
     */
    private string $type;

    public function __construct(Program $program, File $file, string $type, Staff $addedBy)
    {
        $this->program = $program;
        $this->file    = $file;
        $this->type    = $type;
        $this->addedBy = $addedBy;
    }

    public function getProgram(): Program
    {
        return $this->program;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public static function getTypes(): array
    {
        return self::getConstants('TYPE_');
    }
}
