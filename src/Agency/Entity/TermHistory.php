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
 * @ORM\Table(name="agency_term_history")
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
     * @var Term
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Term")
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
     */
    private bool $breach;

    /**
     * @var string|null
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
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $waiver;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $waiverComment;

    /**
     * granted delay by the agent to the borrower for the next answer
     *
     * @var int|null
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
        $this->document        = $term->getBorrowerDocument();
        $this->borrowerInput   = $term->getBorrowerComment();
        $this->borrowerComment = $term->getBorrowerInput();
        $this->agentComment    = $term->getAgentComment();

        $this->update($term);
    }

    /**
     * @param Term $term
     */
    public function update(Term $term)
    {
        $this->validation     = $term->getValidation();
        $this->validationDate = $term->getValidationDate();

        // Irregularity fields
        $this->grantedDelay  = $term->getGrantedDelay();
        $this->breach        = $term->hasBreach();
        $this->breachComment = $term->getBreachComment();
        $this->waiver        = $term->getWaiver();
        $this->waiverComment = $term->getWaiverComment();
    }

    /**
     * @return bool|null
     */
    public function getValidation(): ?bool
    {
        return $this->validation;
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
     * @return string|null
     */
    public function getAgentComment(): ?string
    {
        return $this->agentComment;
    }

    /**
     * @param string|null $borrowerComment
     *
     * @return TermHistory
     */
    public function setBorrowerComment(?string $borrowerComment): TermHistory
    {
        $this->borrowerComment = $borrowerComment;

        return $this;
    }

    /**
     * @param string|null $agentComment
     *
     * @return TermHistory
     */
    public function setAgentComment(?string $agentComment): TermHistory
    {
        $this->agentComment = $agentComment;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBreach(): bool
    {
        return $this->breach;
    }

    /**
     * @param bool $breach
     *
     * @return TermHistory
     */
    public function setBreach(bool $breach): TermHistory
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
     * @return TermHistory
     */
    public function setBreachComment(?string $breachComment): TermHistory
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
     * @return TermHistory
     */
    public function setWaiver(?bool $waiver): TermHistory
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
     * @return TermHistory
     */
    public function setWaiverComment(?string $waiverComment): TermHistory
    {
        $this->waiverComment = $waiverComment;

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
     * While it is not used directly, it may be useful to keep for reference
     * beware : this calculation is base on the current data of the covenant not the one at the creation
     * of this history entry
     *
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

                $rule = $this->getTerm()->getCovenantRule();

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
     * @return bool
     */
    public function hasWaiver(): bool
    {
        return $this->waiver;
    }

    /**
     * @return int|null
     */
    public function getGrantedDelay(): ?int
    {
        return $this->grantedDelay;
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
