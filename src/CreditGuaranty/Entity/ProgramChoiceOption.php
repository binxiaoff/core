<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

class ProgramChoiceOption
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    // User defined
    private const LIST_USER_BORROWER_TYPE       = 'user_borrower_type';
    private const LIST_USER_INVESTMENT_THEMATIC = 'user_investment_thematic';
    private const LIST_USER_FUNDING_OBJECT      = 'user_funding_object';

    // Pre-defined
    private const LIST_LEGAL_FORMS = 'legal_forms';
    private const LIST_NAF_CODE = 'naf_code';
    private const LIST_LOAN_TYPE = 'loan_type';

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     */
    private Program $program;

    /**
     * @ORM\Column(type="text", length=65535)
     */
    private string $description;

    /**
     * @ORM\Column(length=100)
     */
    private string $listName;

    /**
     * @param Program $program
     * @param string  $description
     * @param string  $group
     */
    public function __construct(Program $program, string $description, string $group)
    {
        $this->program     = $program;
        $this->description = $description;
        $this->listName    = $group;
    }

    /**
     * @return Program
     */
    public function getProgram(): Program
    {
        return $this->program;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return ProgramChoiceOption
     */
    public function setDescription(string $description): ProgramChoiceOption
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getListName(): string
    {
        return $this->listName;
    }
}
