<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="agency_term_history", indexes={@ORM\Index(columns={"added"})})
 */
class TermHistory
{
    use TimestampableAddedOnlyTrait;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Term")
     * @ORM\JoinColumn(name="id_term", onDelete="CASCADE")
     *
     * @Assert\NotBlank
     */
    private Term $term;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\File")
     * @ORM\JoinColumn(name="id_document", nullable=true)
     */
    private ?File $document;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $borrowerComment;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $agentComment;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $borrowerInput;

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
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $validationDate = null;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private bool $breach;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $irregularityComment;

    /**
     * true if waiver is granted
     * false if waiver is refused
     * null otherwise.
     *
     * can only be true or false if agent declared breach (TermAnswer::hasBreach() === true)
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $waiver;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $waiverComment;

    /**
     * granted delay by the agent to the borrower for the next answer.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $grantedDelay;

    /**
     * @throws Exception
     */
    public function __construct(Term $term)
    {
        $this->added           = new DateTimeImmutable();
        $this->term            = $term;
        $this->document        = $term->getBorrowerDocument();
        $this->borrowerInput   = $term->getBorrowerComment();
        $this->borrowerComment = $term->getBorrowerInput();
        $this->agentComment    = $term->getAgentComment();

        $this->update($term);
    }

    public function update(Term $term)
    {
        $this->validation     = $term->getValidation();
        $this->validationDate = $term->getValidationDate();

        // Irregularity fields
        $this->grantedDelay        = $term->getGrantedDelay();
        $this->breach              = $term->hasBreach();
        $this->irregularityComment = $term->getIrregularityComment();
        $this->waiver              = $term->getWaiver();
        $this->waiverComment       = $term->getWaiverComment();
    }

    public function getValidation(): ?bool
    {
        return $this->validation;
    }

    public function getValidationDate(): ?DateTimeImmutable
    {
        return $this->validationDate;
    }

    public function getBorrowerComment(): ?string
    {
        return $this->borrowerComment;
    }

    public function getAgentComment(): ?string
    {
        return $this->agentComment;
    }

    public function setBorrowerComment(?string $borrowerComment): TermHistory
    {
        $this->borrowerComment = $borrowerComment;

        return $this;
    }

    public function setAgentComment(?string $agentComment): TermHistory
    {
        $this->agentComment = $agentComment;

        return $this;
    }

    public function isBreach(): bool
    {
        return $this->breach;
    }

    public function setBreach(bool $breach): TermHistory
    {
        $this->breach = $breach;

        return $this;
    }

    public function getIrregularityComment(): ?string
    {
        return $this->irregularityComment;
    }

    public function setIrregularityComment(?string $irregularityComment): TermHistory
    {
        $this->irregularityComment = $irregularityComment;

        return $this;
    }

    public function getWaiver(): ?bool
    {
        return $this->waiver;
    }

    public function isWaiverGranted(): bool
    {
        return true === $this->waiver;
    }

    public function isWaiverRefused(): bool
    {
        return false === $this->waiver;
    }

    /**
     * @param bool $waiver
     */
    public function setWaiver(?bool $waiver): TermHistory
    {
        $this->waiver = $waiver;

        return $this;
    }

    public function getWaiverComment(): ?string
    {
        return $this->waiverComment;
    }

    public function setWaiverComment(?string $waiverComment): TermHistory
    {
        $this->waiverComment = $waiverComment;

        return $this;
    }

    public function getBorrowerInput(): ?string
    {
        return $this->borrowerInput;
    }

    /**
     * While it is not used directly, it may be useful to keep for reference
     * beware : this calculation is base on the current data of the covenant not the one at the creation
     * of this history entry.
     */
    public function isFulfilled(): bool
    {
        switch ($this->getTerm()->getNature()) {
            case Covenant::NATURE_CONTROL:
                return true;

            case Covenant::NATURE_DOCUMENT:
                return null !== $this->getDocument();

            case Covenant::NATURE_FINANCIAL_ELEMENT:
            case Covenant::NATURE_FINANCIAL_RATIO:
                if (null === $this->borrowerInput || false === is_numeric($this->borrowerInput)) {
                    return false;
                }

                $rule = $this->getTerm()->getCovenantRule();

                if (null === $rule) {
                    return false;
                }

                return $rule->getInequality()->isConform($this->borrowerInput);
        }

        return false;
    }

    public function getTerm(): Term
    {
        return $this->term;
    }

    public function getDocument(): ?File
    {
        return $this->document;
    }

    public function hasWaiver(): bool
    {
        return $this->waiver;
    }

    public function getGrantedDelay(): ?int
    {
        return $this->grantedDelay;
    }

    public function isValid(): bool
    {
        return true === $this->validation;
    }

    public function isInvalid(): bool
    {
        return false === $this->validation;
    }

    public function isPendingAgentValidation(): bool
    {
        return null === $this->validation;
    }
}
