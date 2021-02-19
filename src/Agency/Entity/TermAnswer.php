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
     * @var bool
     *
     * True if borrower fulfill contract conditions
     * False if borrower fail to fulfill it
     * Null if answer is pending
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
    private ?File $document = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $borrowerComment = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $agentComment = null;

    /**
     * @param Term $term
     *
     * @throws Exception
     */
    public function __construct(Term $term)
    {
        $this->added = new DateTimeImmutable();
        $this->term  = $term;
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
     * @param bool $validation
     *
     * @return TermAnswer
     *
     * @throws Exception
     */
    public function setValidation(bool $validation): TermAnswer
    {
        if ($this->validation === null) {
            $this->validation     = $validation;
            $this->validationDate = new DateTimeImmutable();
        }

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
        }

        return false;
    }
}
