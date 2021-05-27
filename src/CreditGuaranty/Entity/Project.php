<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Interfaces\MoneyInterface;
use Unilend\Core\Entity\NafNace;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\Core\Service\MoneyCalculator;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "creditGuaranty:project:read",
 *         "money:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "creditGuaranty:project:write",
 *         "money:write"
 *     }},
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)"
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)"
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)"
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_project")
 * @ORM\HasLifecycleCallbacks
 */
class Project
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\CreditGuaranty\Entity\Reservation", mappedBy="project")
     */
    private Reservation $reservation;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private Money $fundingMoney;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_program_choice_option", nullable=false)
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private ProgramChoiceOption $investmentThematic;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\NafNace")
     * @ORM\JoinColumn(name="id_naf_nace", nullable=false)
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NafNace $nafNace;

    public function __construct(Money $fundingMoney, ProgramChoiceOption $investmentThematic, NafNace $nafNace)
    {
        $this->fundingMoney       = $fundingMoney;
        $this->investmentThematic = $investmentThematic;
        $this->nafNace            = $nafNace;
        $this->added              = new DateTimeImmutable();
    }

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }

    public function getFundingMoney(): Money
    {
        return $this->fundingMoney;
    }

    public function setFundingMoney(Money $fundingMoney): Project
    {
        $this->fundingMoney = $fundingMoney;

        return $this;
    }

    public function getInvestmentThematic(): ProgramChoiceOption
    {
        return $this->investmentThematic;
    }

    public function setInvestmentThematic(ProgramChoiceOption $investmentThematic): Project
    {
        $this->investmentThematic = $investmentThematic;

        return $this;
    }

    public function getNafNace(): NafNace
    {
        return $this->nafNace;
    }

    public function setNafNace(NafNace $nafNace): Project
    {
        $this->nafNace = $nafNace;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }

    /**
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function checkBalance(): bool
    {
        $program    = $this->reservation->getProgram();
        $totalFunds = $this->getTotalFunds($program);

        return MoneyCalculator::compare($totalFunds, $program->getFunds()) <= 0;
    }

    public function checkQuota(): bool
    {
        $program       = $this->reservation->getProgram();
        $participation = $program->getParticipations()->get($this->reservation->getManagingCompany()->getId());

        if (false === $participation instanceof Participation) {
            throw new \RuntimeException(sprintf(
                'Cannot find the participation company %d for reservation %d. Please check the date.',
                $this->reservation->getManagingCompany()->getId(),
                $this->getId()
            ));
        }

        $ratio = MoneyCalculator::ratio($this->getFundingMoney(), $program->getFunds());

        return bccomp((string) $ratio, $participation->getQuota(), 4) <= 0;
    }

    public function checkGradeAllocation(): bool
    {
        $program    = $this->reservation->getProgram();
        $grade      = $this->reservation->getBorrower()->getGrade();
        $gradeFunds = $this->getTotalFunds($program, ['grade' => $grade]);
        $ratio      = MoneyCalculator::ratio($gradeFunds, $program->getFunds());
        /** @var ProgramGradeAllocation $programGradeAllocation */
        $programGradeAllocation = $program->getProgramGradeAllocations()->get($grade);

        return bccomp((string) $ratio, $programGradeAllocation->getMaxAllocationRate(), 4) <= 0;
    }

    public function checkBorrowerTypeAllocation(): bool
    {
        $program      = $this->reservation->getProgram();
        $borrowerType = $this->reservation->getBorrower()->getBorrowerType();
        if (false === $borrowerType instanceof ProgramChoiceOption) {
            throw new \RuntimeException(sprintf(
                'Cannot find the borrower type %d for reservation %d. Please check the date.',
                $borrowerType->getId(),
                $this->reservation->getId()
            ));
        }
        $borrowerTypeFunds = $this->getTotalFunds($program, ['borrowerType' => $borrowerType->getId()]);
        $ratio             = MoneyCalculator::ratio($borrowerTypeFunds, $program->getFunds());
        /** @var ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation */
        $programBorrowerTypeAllocation = $program->getProgramBorrowerTypeAllocations()->get($borrowerType->getId());

        return bccomp((string) $ratio, $programBorrowerTypeAllocation->getMaxAllocationRate(), 4) <= 0;
    }

    private function getTotalFunds(Program $program, array $filters = []): MoneyInterface
    {
        // Since the current project can be in the "total" or not according to its status,
        // we exclude it from the "total", then add it back manually to the "total", so that we get always the same "total" all the time.
        $filters = array_merge(['exclude' => $this->getId()], $filters);

        return MoneyCalculator::add($program->getTotalProjectFunds($filters), $this->getFundingMoney());
    }
}
