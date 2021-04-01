<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use LogicException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="agency_term")
 *
 * @ApiResource(
 *     normalizationContext={
 *        "groups": {"agency:term:read"}
 *     },
 *     attributes={
 *        "validation_groups": {Term::class, "getValidationGroups"}
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": NotFoundAction::class,
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {
 *              "denormalization_context": {
 *                    "groups": {"agency:term:update"}
 *              },
 *              "security": "is_granted('edit', object)"
 *         },
 *         "delete": {
 *              "security": "is_granted('delete', object)"
 *         }
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
     *
     * @Groups({"agency:term:read"})
     *
     * @ApiProperty(writableLink=false, readableLink=false)
     */
    private Covenant $covenant;

    /**
     * @var DateTimeImmutable
     *
     * @Assert\NotBlank
     *
     * @ORM\Column(type="date_immutable")
     *
     * @Groups({"agency:term:read"})
     */
    private DateTimeImmutable $startDate;

    /**
     * @var DateTimeImmutable
     *
     * @Assert\GreaterThan(propertyPath="startDate")
     * @Assert\NotBlank
     *
     * @ORM\Column(type="date_immutable")
     *
     * @Groups({"agency:term:read", "agency:term:update"})
     */
    private DateTimeImmutable $endDate;

    /**
     * @var File|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\File")
     * @ORM\JoinColumn(name="id_document", nullable=true)
     *
     * @Groups({"agency:term:read", "agency:term:update"})
     */
    private ?File $borrowerDocument;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Assert\Expression(
     *     expression="(null === value) || this.getBorrowerInput() || this.getBorrowerDocument()",
     *     message="Agency.Term.borrowerComment.neededBorrowerAction"
     * )
     *
     * @Groups({"agency:term:read", "agency:term:update"})
     */
    private ?string $borrowerComment;

    /**
     * Used for financial covenant, represent the input of the borrower for the given term
     * Must be null for other covenant
     *
     * @var string|null
     *
     * @Assert\Length(max="255")
     * @Assert\Type("numeric")
     * @Assert\IsNull(groups={"Other"}, message="Agency.Term.borrowerInput.otherCovenant")
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups({"agency:term:read", "agency:term:update"})
     */
    private ?string $borrowerInput;

    /**
     * Register the date at with the borrower has given its result
     *
     * @var DateTimeInterface|null
     *
     * @Assert\GreaterThanOrEqual(propertyPath="startDate")
     * @Assert\IsNull(groups={"Other"}, message="Agency.Term.borrowerInputDate.otherCovenant")
     * @Assert\Expression(
     *     groups={"Financial"},
     *     expression="(null === value && null === this.getBorrowerInput()) || (null !== value && null !== this.getBorrowerInput())",
     *     message="Agency.Term.borrowerInputDate.borrowerInput"
     * )
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"agency:term:read"})
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
     *
     * @Groups({"agency:term:read", "agency:term:update"})
     */
    private ?bool $validation;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\GreaterThanOrEqual(propertyPath="startDate")
     * @Assert\Expression(
     *     expression="(null === value && null === this.getValidation()) || (null !== value && null !== this.getValidation())",
     *     message="Agency.Term.validationDate.validation"
     * )
     *
     * @Groups({"agency:term:read"})
     */
    private ?DateTimeImmutable $validationDate;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"agency:term:read", "agency:term:update"})
     */
    private ?string $agentComment;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false)
     *
     * @Assert\Expression(
     *     expression="(value && this.isInvalid()) || !value",
     *     message="Agency.Term.breach.invalidTermRequired"
     * )
     *
     * @Groups({"agency:term:read", "agency:term:update"})
     */
    private bool $breach;

    /**
     * @var string|null
     *
     * @Assert\Expression(
     *     expression="this.hasBreach() || null === value",
     *     message="Agency.Term.breachComment.breachRequired"
     * )
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"agency:term:read", "agency:term:update"})
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
     * @Assert\Expression(
     *     expression="this.hasBreach() || value === null",
     *     message="Agency.Term.waiver.breachRequired"
     * )
     *
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"agency:term:read", "agency:term:update"})
     */
    private ?bool $waiver;

    /**
     * @var string|null
     *
     * @Assert\Expression(
     *     expression="((this.isWaiverGranted() || this.isWaiverRefused()) && value) || null === value",
     *     message="Agency.Term.waiverComment.waiverStatusRequired"
     * )
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"agency:term:read", "agency:term:update"})
     */
    private ?string $waiverComment;

    /**
     * granted delay by the agent to the borrower for the next answer
     *
     * @var int|null
     *
     * @Assert\Positive
     *
     * @Assert\Expression("this.isInvalid() || value === null", message="Agency.Term.grantedDelay.invalidTermRequired")
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Groups({"agency:term:read", "agency:term:update"})
     */
    private ?int $grantedDelay;

    /**
     * @var DateTimeImmutable|null
     *
     * @Assert\GreaterThanOrEqual(propertyPath="validationDate")
     * @Assert\Expression(
     *     expression="(value && (false === this.isPendingAgentValidation())) || (null === value)",
     *     message="Agency.Term.sharingDate.agentValidationRequired"
     * )
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Groups({"agency:term:read"})
     */
    private ?DateTimeImmutable $sharingDate;

    /**
     * @var DateTimeImmutable|null
     *
     * @Assert\GreaterThanOrEqual(propertyPath="sharingDate")
     * @Assert\Expression(
     *     expression="(value && this.isShared()) || (null === value)",
     *     message="Agency.Term.sharingDate.poolSharingRequired"
     * )
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Groups({"agency:term:read"})
     */
    private ?DateTimeImmutable $archivingDate;

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

        $this->borrowerDocument = null;
        $this->borrowerComment = null;
        $this->borrowerInput = null;
        $this->borrowerInputDate = null;

        $this->agentComment = null;

        $this->validation = null;
        $this->validationDate = null;

        $this->sharingDate = null;
        $this->archivingDate = null;

        // Irregularity fields
        $this->grantedDelay = null;
        $this->breach = false;
        $this->breachComment = null;
        $this->waiver = null;
        $this->waiverComment = null;
    }

    /**
     * @return bool
     *
     * @Groups({"agency:term:read"})
     */
    public function isFulfilled(): bool
    {
        switch ($this->getNature()) {
            case Covenant::NATURE_CONTROL:
                return true;
            case Covenant::NATURE_DOCUMENT:
                return null !== $this->getBorrowerDocument() && $this->isDateValid($this->getBorrowerDocument()->getAdded());
            case Covenant::NATURE_FINANCIAL_ELEMENT:
            case Covenant::NATURE_FINANCIAL_RATIO:
                if (null === $this->borrowerInput || false === is_numeric($this->borrowerInput)) {
                    return false;
                }

                if (null === $this->borrowerInputDate || false === $this->isDateValid($this->borrowerInputDate)) {
                    return false;
                }

                $rule = $this->getCovenantRule();

                if (null === $rule) {
                    return false;
                }

                return $rule->getInequality()->isConform($this->borrowerInput);
        }

        throw new LogicException(sprintf('The given type (%s) is incorrect for a term', $this->getNature()));
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
    public function getBorrowerDocument(): ?File
    {
        return $this->borrowerDocument;
    }

    /**
     * @param File|null $borrowerDocument
     *
     * @return Term
     */
    public function setBorrowerDocument(?File $borrowerDocument): Term
    {
        $this->borrowerDocument = $borrowerDocument;
        $this->awaitValidation();

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
     *
     * @Groups({"agency:term:read"})
     */
    public function getCovenantRule(): ?CovenantRule
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

        $this->awaitValidation();

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
     *
     * @Groups({"agency:term:read"})
     */
    public function isWaiverGranted(): bool
    {
        return true === $this->waiver;
    }

    /**
     * @return bool
     *
     * @Groups({"agency:term:read"})
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
     *
     * @Groups({"agency:term:read"})
     */
    public function isValid(): bool
    {
        return true === $this->validation;
    }

    /**
     * @return bool
     *
     * @Groups({"agency:term:read"})
     */
    public function isInvalid(): bool
    {
        return false === $this->validation;
    }

    /**
     * @return bool
     *
     * @Groups({"agency:term:read"})
     */
    public function isPendingAgentValidation(): bool
    {
        return null === $this->validation;
    }

    /**
     * @return bool
     *
     * @Groups({"agency:term:read"})
     */
    public function isPendingBorrowerInput(): bool
    {
        switch ($this->getNature()) {
            case Covenant::NATURE_CONTROL:
                return false;
            case Covenant::NATURE_DOCUMENT:
                return null === $this->getBorrowerDocument();
            case Covenant::NATURE_FINANCIAL_ELEMENT:
            case Covenant::NATURE_FINANCIAL_RATIO:
                return null === $this->borrowerInput || null === $this->getBorrowerDocument();
        }

        throw new LogicException(sprintf('The given type (%s) is incorrect for a term', $this->getNature()));
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
     *
     * @Groups({"agency:term:read"})
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
     *
     * @Groups({"agency:term:read"})
     */
    public function isArchived(): bool
    {
        return null !== $this->archivingDate;
    }

    /**
     * @param Term $term
     *
     * @return string[]
     */
    public static function getValidationGroups(Term $term): array
    {
        return ['Default', 'Term', $term->isFinancial() ? 'Financial' : 'Other'];
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

    /**
     * Reset entity to await agent validation
     *
     * Used when new value or new document is put
     */
    private function awaitValidation()
    {
        $this->validation = null;
        $this->validationDate = null;
        $this->agentComment = null;

        $this->breach = false;
        $this->breachComment = null;
        $this->waiver = null;
        $this->waiverComment = null;
    }
}
