<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};
use Unilend\CreditGuaranty\Entity\ConstantList\EligibilityCriteria;

/**
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_program_eligibility_configuration")
 */
class ProgramEligibilityConfiguration
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramEligibility")
     * @ORM\JoinColumn(name="id_program_eligibility", nullable=false)
     */
    private ProgramEligibility $programEligibility;

    /**
     * When its value is not null, it means that we configure the eligibility based on the user's choice of the target field.
     * $programChoiceOption and $value cannot be both filed at the same time.
     *
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_program_choice_option")
     *
     * @Groups({"creditGuaranty:programEligibilityConfiguration:read"})
     */
    private ?ProgramChoiceOption $programChoiceOption;

    /**
     * When its value is not null, it means that we configure the eligibility based on the value of the target field.
     * $programChoiceOption and $value cannot be both filed at the same time.
     *
     * @ORM\Column(length=100, nullable=true)
     *
     * @Groups({"creditGuaranty:programEligibilityConfiguration:read"})
     */
    private ?string $value;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"creditGuaranty:programEligibilityConfiguration:read"})
     */
    private bool $eligible;

    /**
     * @param ProgramEligibility       $programEligibility
     * @param ProgramChoiceOption|null $programChoiceOption
     * @param string|null              $value
     * @param bool                     $eligible
     */
    public function __construct(ProgramEligibility $programEligibility, ?ProgramChoiceOption $programChoiceOption, ?string $value, bool $eligible = false)
    {
        $this->programEligibility  = $programEligibility;
        $this->programChoiceOption = $programChoiceOption;
        $this->value               = $value;
        $this->eligible            = $eligible;
        $this->added               = new \DateTimeImmutable();
    }

    /**
     * @return ProgramEligibility
     */
    public function getProgramEligibility(): ProgramEligibility
    {
        return $this->programEligibility;
    }

    /**
     * @param bool $eligible
     *
     * @return ProgramEligibilityConfiguration
     */
    public function setEligible(bool $eligible): ProgramEligibilityConfiguration
    {
        $this->eligible = $eligible;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEligible(): bool
    {
        return $this->eligible;
    }

    /**
     * @return ProgramChoiceOption|null
     */
    public function getProgramChoiceOption(): ?ProgramChoiceOption
    {
        return $this->programChoiceOption;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function validateConfiguration(ExecutionContextInterface $context): void
    {
        $criteriaType   = $this->getProgramEligibility()->getEligibilityCriteria()->getType();
        $violationPaths = [];
        $valid          = true;
        switch ($criteriaType) {
            case EligibilityCriteria::TYPE_OTHER:
                if (null !== $this->value) {
                    $violationPaths[] = 'value';
                    $valid            = false;
                }
                if (null !== $this->programChoiceOption) {
                    $violationPaths[] = 'programChoiceOption';
                    $valid            = false;
                }
                break;
            case EligibilityCriteria::TYPE_BOOL:
                if (null === $this->value) {
                    $violationPaths[] = 'value';
                    $valid            = false;
                }
                if (null !== $this->programChoiceOption) {
                    $violationPaths[] = 'programChoiceOption';
                    $valid            = false;
                }
                break;
            case EligibilityCriteria::TYPE_LIST:
                if (null !== $this->value) {
                    $violationPaths[] = 'value';
                    $valid            = false;
                }
                if (null === $this->programChoiceOption) {
                    $violationPaths[] = 'programChoiceOption';
                    $valid            = false;
                }
                break;
            default:
                $context->buildViolation('CreditGuaranty.ProgramEligibility.eligibilityCriteria.unsupportedType')
                    ->atPath('programEligibility.criteria')
                    ->addViolation()
                ;

                return;
        }

        if (false === $valid) {
            foreach ($violationPaths as $path) {
                $context->buildViolation('CreditGuaranty.ProgramEligibilityConfiguration.' . $path . '.invalid')
                    ->atPath($path)
                    ->addViolation()
                ;
            }
        }
    }
}
