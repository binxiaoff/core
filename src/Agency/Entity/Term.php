<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="agency_term")
 *
 * @ApiResource(
 *     itemOperations={
 *         "get",
 *         "patch",
 *         "delete"
 *     },
 *     collectionOperations={}
 * )
 */
class Term
{
    use PublicizeIdentityTrait;

    /**
     * @var Covenant
     *
     * @Assert\NotBlank
     *
     * @ORM\ManyToOne(targetEntity=Covenant::class, inversedBy="terms")
     * @ORM\JoinColumn(name="id_covenant", onDelete="CASCADE")
     */
    private Covenant $covenant;

    /**
     * @var DateTimeImmutable
     *
     * @Assert\NotBlank
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $startDate;

    /**
     * @var DateTimeImmutable
     *
     * @Assert\GreaterThan(propertyPath="startDate")
     * @Assert\NotBlank
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $endDate;

    /**
     * @var DateTimeImmutable|null
     *
     * @Assert\GreaterThanOrEqual(propertyPath="startDate")
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $sharingDate;

    /**
     * @var DateTimeImmutable|null
     *
     * @Assert\GreaterThanOrEqual(propertyPath="startDate")
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $archivingDate;

    /**
     * @var File|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\File")
     * @ORM\JoinColumn(name="id_document", nullable=true)
     */
    private ?File $document;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $borrowerComment;

    /**
     * @var string|null
     *
     * @Assert\Length(max="255")
     * @Assert\Type("numeric")
     *
     * @Assert\Expression("(this.getTerm().isFinancial() && value) || (!this.getTerm().isFinancial() && !value)")
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $borrowerInput;

    /**
     * @var DateTimeInterface|null
     *
     * @Assert\GreaterThanOrEqual(propertyPath="startDate")
     * @Assert\Expression("(value && $this->getBorrowerInput()) || (!value && !$this->getBorrowerInput())")
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeInterface $borrowerInputDate;

    /**
     * @var bool
     *
     * True if agent deems borrower answer correct
     * False if agent deems borrower answer incorrect (incorrectDocument, late answer, etc.) => breach of covenant if shared, risk of breach if not shared
     * Null if agent has not yet express validation on answer
     *
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Assert\Type("bool")
     */
    private ?bool $validation = null;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $validationDate = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $agentComment;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false)
     *
     * @Assert\Expression("(value && this.isInvalid()) || !value")
     */
    private bool $breach;

    /**
     * @var string|null
     *
     * @Assert\Expression("(this.hasBreach) && value) || null === value")
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $breachComment;

    /**
     * true if waiver is granted
     * false if waiver is refused
     * null otherwise
     *
     * can only be true or false if agent declared breach (Term::hasBreach() === true)
     *
     * @var bool
     *
     * @Assert\Expression("this.hasBreach() || value === null")
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $waiver;

    /**
     * @var string|null
     *
     * @Assert\Expression("((this.isWaiverGranted() || this.isWaiverRefused()) && value) || null === value")
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $waiverComment;

    /**
     * granted delay by the agent to the borrower for the next answer
     *
     * @var int|null
     *
     * @Assert\Positive
     *
     * @Assert\Expression("this.isInvalid() || value === null")
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $grantedDelay;

    /**
     * @param Covenant               $covenant
     * @param DateTimeImmutable      $startDate
     * @param DateTimeImmutable|null $endDate
     */
    public function __construct(Covenant $covenant, DateTimeImmutable $startDate, ?DateTimeImmutable $endDate = null)
    {
        $this->covenant = $covenant;
        $this->startDate = $startDate;
        $this->endDate = $endDate ?? $startDate->add(DateInterval::createFromDateString('+ ' . $covenant->getDelay() . ' days'));

        $this->document = null;
        $this->borrowerComment = null;
        $this->borrowerInput = null;
        $this->borrowerInputDate = null;

        $this->agentComment = null;

        $this->validation = null;
        $this->validationDate = null;

        // Irregularity fields
        $this->grantedDelay = null;
        $this->breach = false;
        $this->breachComment = null;
        $this->waiver = null;
        $this->waiverComment = null;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getSharingDate(): ?DateTimeImmutable
    {
        return $this->sharingDate;
    }

    /**
     * @return Term
     *
     * @throws Exception
     */
    public function share(): Term
    {
        if ($this->isShared()) {
            return $this;
        }

        $this->sharingDate = new DateTimeImmutable();

        return $this;
    }

    /**
     * @return bool
     */
    public function isShared(): bool
    {
        return null !== $this->sharingDate;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getArchivingDate(): ?DateTimeImmutable
    {
        return $this->archivingDate;
    }

    /**
     * @return Term
     *
     * @throws Exception
     */
    public function archive(): Term
    {
        if ($this->isArchived()) {
            return $this;
        }

        $this->archivingDate = new DateTimeImmutable();

        return $this;
    }

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return null !== $this->archivingDate;
    }

    /**
     * @return bool
     */
    public function isFulfilled(): bool
    {
        switch ($this->getNature()) {
            case Covenant::NATURE_CONTROL:
                return true;
            case Covenant::NATURE_DOCUMENT:
                return null !== $this->getDocument() && $this->isDateValid($this->getDocument()->getAdded());
            case Covenant::NATURE_FINANCIAL_ELEMENT:
            case Covenant::NATURE_FINANCIAL_RATIO:
                if (null === $this->borrowerInput || false === is_numeric($this->borrowerInput)) {
                    return false;
                }

                if (null === $this->borrowerInputDate || false === $this->isDateValid($this->borrowerInputDate)) {
                    return false;
                }

                $rule = $this->getFinancialRule();

                if (null === $rule) {
                    return false;
                }

                return $rule->getInequality()->isConform($this->borrowerInput);
        }

        return false;
    }

    /**
     * @return string
     */
    public function getNature(): string
    {
        return $this->getCovenant()->getNature();
    }

    /**
     * @return Covenant
     */
    public function getCovenant(): Covenant
    {
        return $this->covenant;
    }

    /**
     * @return File|null
     */
    public function getDocument(): ?File
    {
        return $this->document;
    }

    /**
     * @param File|null $document
     *
     * @return Term
     */
    public function setDocument(?File $document): Term
    {
        $this->document = $document;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    /**
     * @param DateTimeImmutable $endDate
     *
     * @return Term
     */
    public function setEndDate(DateTimeImmutable $endDate): Term
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return CovenantRule|null
     */
    public function getFinancialRule(): ?CovenantRule
    {
        if (false === $this->isFinancial()) {
            return null;
        }

        $year = (int) $this->getStartDate()->format('Y');

        return $this->getCovenant()->getCovenantRules()[$year];
    }

    /**
     * @return bool
     */
    public function isFinancial(): bool
    {
        return $this->getCovenant()->isFinancial();
    }

    /**
     * @return bool|null
     */
    public function getValidation(): ?bool
    {
        return $this->validation;
    }

    /**
     * @param bool $validation
     *
     * @return Term
     *
     * @throws Exception
     */
    public function setValidation(bool $validation): Term
    {
        $this->validation = $validation;
        $this->validationDate = null !== $validation ? new DateTimeImmutable() : null;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getValidationDate(): ?DateTimeImmutable
    {
        return $this->validationDate;
    }

    /**
     * @return string|null
     */
    public function getBorrowerComment(): ?string
    {
        return $this->borrowerComment;
    }

    /**
     * @param string|null $borrowerComment
     *
     * @return Term
     */
    public function setBorrowerComment(?string $borrowerComment): Term
    {
        $this->borrowerComment = $borrowerComment;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAgentComment(): ?string
    {
        return $this->agentComment;
    }

    /**
     * @param string|null $agentComment
     *
     * @return Term
     */
    public function setAgentComment(?string $agentComment): Term
    {
        $this->agentComment = $agentComment;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBorrowerInput(): ?string
    {
        return $this->borrowerInput;
    }

    /**
     * @param string|null $borrowerInput
     *
     * @return Term
     */
    public function setBorrowerInput(?string $borrowerInput): Term
    {
        $this->borrowerInput = $borrowerInput;
        $this->borrowerInputDate = null !== $borrowerInput ? new DateTimeImmutable() : null;

        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getBorrowerInputDate(): ?DateTimeInterface
    {
        return $this->borrowerInputDate;
    }

    /**
     * @return int|null
     */
    public function getGrantedDelay(): ?int
    {
        return $this->grantedDelay;
    }

    /**
     * @param int|null $grantedDelay
     *
     * @return Term
     */
    public function setGrantedDelay(?int $grantedDelay): Term
    {
        $this->grantedDelay = $grantedDelay;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasBreach(): bool
    {
        return $this->breach;
    }

    /**
     * @param bool $breach
     *
     * @return Term
     */
    public function setBreach(bool $breach): Term
    {
        $this->breach = $breach;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBreachComment(): ?string
    {
        return $this->breachComment;
    }

    /**
     * @param string|null $breachComment
     *
     * @return Term
     */
    public function setBreachComment(?string $breachComment): Term
    {
        $this->breachComment = $breachComment;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getWaiver(): ?bool
    {
        return $this->waiver;
    }

    /**
     * @return bool
     */
    public function isWaiverGranted(): bool
    {
        return true === $this->waiver;
    }

    /**
     * @return bool
     */
    public function isWaiverRefused(): bool
    {
        return false === $this->waiver;
    }

    /**
     * @param bool $waiver
     *
     * @return Term
     */
    public function setWaiver(bool $waiver): Term
    {
        $this->waiver = $waiver;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getWaiverComment(): ?string
    {
        return $this->waiverComment;
    }

    /**
     * @param string|null $waiverComment
     *
     * @return Term
     */
    public function setWaiverComment(?string $waiverComment): Term
    {
        $this->waiverComment = $waiverComment;

        return $this;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return true === $this->validation;
    }

    /**
     * @return bool
     */
    public function isInvalid(): bool
    {
        return false === $this->validation;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return null === $this->validation;
    }

    /**
     * @param DateTimeInterface $dateTime
     *
     * @return bool
     */
    private function isDateValid(DateTimeInterface $dateTime)
    {
        return $this->getStartDate()->setTime(0, 0) <= $dateTime && $dateTime <= $this->getEndDate()->setTime(0, 0);
    }
}
