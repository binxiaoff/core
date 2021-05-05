<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Constant\CAInternalRating;
use Unilend\Core\Entity\Constant\CAInternalRetailRating;
use Unilend\Core\Entity\Constant\CARatingType;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"creditGuaranty:programGradeAllocation:read", "creditGuaranty:program:read", "timestampable:read"}},
 *     denormalizationContext={"groups": {"creditGuaranty:programGradeAllocation:write"}},
 *     itemOperations={
 *         "get",
 *         "patch": {"security_post_denormalize": "is_granted('edit', previous_object)"},
 *         "delete": {"security": "is_granted('delete', object)"}
 *     },
 *     collectionOperations={
 *         "post": {"security_post_denormalize": "is_granted('create', object)"}
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="credit_guaranty_program_grade_allocation",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"id_program", "grade"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"program", "grade"})
 */
class ProgramGradeAllocation
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program", inversedBy="programGradeAllocations")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:programGradeAllocation:read", "creditGuaranty:programGradeAllocation:write"})
     */
    private Program $program;

    /**
     * @ORM\Column(length=10)
     *
     * @Assert\Expression(
     *     "this.isGradeValid()",
     *     message="CreditGuaranty.ProgramGradeAllocation.grade.invalid"
     * )
     *
     * @Groups({"creditGuaranty:programGradeAllocation:read", "creditGuaranty:programGradeAllocation:write"})
     */
    private string $grade;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="1")
     *
     * @Groups({"creditGuaranty:programGradeAllocation:read", "creditGuaranty:programGradeAllocation:write"})
     */
    private string $maxAllocationRate;

    public function __construct(Program $program, string $grade, string $maxAllocationRate)
    {
        $this->program           = $program;
        $this->grade             = $grade;
        $this->maxAllocationRate = $maxAllocationRate;
        $this->added             = new DateTimeImmutable();
    }

    public function getProgram(): Program
    {
        return $this->program;
    }

    public function getGrade(): string
    {
        return $this->grade;
    }

    public function setGrade(string $grade): ProgramGradeAllocation
    {
        $this->grade = $grade;

        return $this;
    }

    public function getMaxAllocationRate(): string
    {
        return $this->maxAllocationRate;
    }

    public function setMaxAllocationRate(string $maxAllocationRate): ProgramGradeAllocation
    {
        $this->maxAllocationRate = $maxAllocationRate;

        return $this;
    }

    public function isGradeValid(): bool
    {
        switch ($this->program->getRatingType()) {
            case CARatingType::CA_INTERNAL_RETAIL_RATING:
                return \in_array($this->grade, CAInternalRetailRating::getConstList());

            case CARatingType::CA_INTERNAL_RATING:
                return \in_array($this->grade, CAInternalRating::getConstList());

            default:
                return false;
        }
    }
}
