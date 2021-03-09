<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="agency_term_answer")
 */
class TermAnswer
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @var Term
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Term", inversedBy="answers")
     * @ORM\JoinColumn(name="id_term", onDelete="CASCADE")
     *
     * @Assert\NotBlank
     */
    private Term $term;

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
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $agentComment;

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
     * can only be true or false if agent declared breach (TermAnswer::hasBreach() === true)
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
     * @param Term $term
     *
     * @throws Exception
     */
    public function __construct(Term $term)
    {
        $this->added           = new DateTimeImmutable();
        $this->term            = $term;
        $this->document        = null;
        $this->borrowerInput   = null;
        $this->borrowerComment = null;
        $this->agentComment    = null;

        $this->validation     = null;
        $this->validationDate = null;

        // Irregularity fields
        $this->grantedDelay  = null;
        $this->breach        = false;
        $this->breachComment = null;
        $this->waiver        = null;
        $this->waiverComment = null;
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
     * @return TermAnswer
     *
     * @throws Exception
     */
    public function setValidation(bool $validation): TermAnswer
    {
        if (null === $this->validation) {
            $this->validation = $validation;
            $this->validationDate = new DateTimeImmutable();
        }

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
     * @return TermAnswer
     */
    public function setBorrowerComment(?string $borrowerComment): TermAnswer
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
     * @return TermAnswer
     */
    public function setAgentComment(?string $agentComment): TermAnswer
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
     * @return TermAnswer
     */
    public function setBorrowerInput(?string $borrowerInput): TermAnswer
    {
        $this->borrowerInput = $borrowerInput;

        return $this;
    }

    /**
     * @return bool
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

                $rule = $this->getTerm()->getFinancialRule();

                if (null === $rule) {
                    return false;
                }

                return $rule->getInequality()->isConform($this->borrowerInput);
        }

        return false;
    }

    /**
     * @return Term
     */
    public function getTerm(): Term
    {
        return $this->term;
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
     * @return TermAnswer
     */
    public function setDocument(?File $document): TermAnswer
    {
        $this->document = $document;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasWaiver(): bool
    {
        return $this->waiver;
    }

    /**
     * @param bool $waiver
     *
     * @return TermAnswer
     */
    public function setWaiver(bool $waiver): TermAnswer
    {
        $this->waiver = $waiver;

        return $this;
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
     * @return TermAnswer
     */
    public function setGrantedDelay(?int $grantedDelay): TermAnswer
    {
        $this->grantedDelay = $grantedDelay;

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
}
