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
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Assert\Type("bool")
     */
    private ?bool $valid = null;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $validated = null;

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
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $borrowerComment = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
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
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getValidated(): ?DateTimeImmutable
    {
        return $this->validated;
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
     */
    public function setDocument(?File $document): void
    {
        $this->document = $document;
    }

    /**
     * @param string|null $borrowerComment
     */
    public function setBorrowerComment(?string $borrowerComment): void
    {
        $this->borrowerComment = $borrowerComment;
    }

    /**
     * @param string|null $agentComment
     */
    public function setAgentComment(?string $agentComment): void
    {
        $this->agentComment = $agentComment;
    }
}
