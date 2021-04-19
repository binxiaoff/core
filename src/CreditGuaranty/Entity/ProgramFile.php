<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\{File, Staff, Traits\BlamableAddedTrait, Traits\PublicizeIdentityTrait, Traits\TimestampableAddedOnlyTrait};
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

    /**
     * @param Program $program
     * @param File    $file
     * @param string  $type
     * @param Staff   $addedBy
     */
    public function __construct(Program $program, File $file, string $type, Staff $addedBy)
    {
        $this->program = $program;
        $this->file    = $file;
        $this->type    = $type;
        $this->addedBy = $addedBy;
    }

    /**
     * @return Program
     */
    public function getProgram(): Program
    {
        return $this->program;
    }

    /**
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public static function getTypes(): array
    {
        return self::getConstants('TYPE_');
    }
}
