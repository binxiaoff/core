<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectAbandonReason
 *
 * @ORM\Table(name="project_abandon_reason")
 * @ORM\Entity
 */
class ProjectAbandonReason
{
    const STATUS_OFFLINE = 0;
    const STATUS_ONLINE  = 1;

    const UNSUBSCRIBE_FROM_EMAIL_REMINDER        = 'unsubscribe_from_email_reminder';
    const OTHER_PROJECT_OF_SAME_COMPANY_REJECTED = 'other_project_of_same_company_rejected';
    const NOT_ELIGIBLE                           = 'not_eligible';
    const BORROWER_NOT_INTERESTED                = 'borrower_not_interested';
    const BORROWER_CONTACT_UNSUCCESSFUL          = 'borrower_contact_unsuccessful';
    const BORROWER_FOLLOW_UP_UNSUCCESSFUL        = 'borrower_follow_up_unsuccessful';
    const FUNDED_OTHERWISE                       = 'funded_otherwise';
    const PROJECT_DUPLICATED                     = 'project_duplicated';
    const TOTAL_FUNDING_COST_TOO_HIGH            = 'total_funding_cost_too_high';
    const INTEREST_RATE_RANGE_TOO_HIGH           = 'interest_rate_range_too_high';
    const FUNDED_OTHERWISE_BANK                  = 'funded_otherwise_bank';
    const FUNDED_OTHERWISE_DIRECT                = 'funded_otherwise_direct';
    const FUNDED_OTHERWISE_OTHER                 = 'funded_otherwise_other';
    const SLOW_UNILEND_RESPONSE                  = 'slow_unilend_response';
    const PROJECT_ABORTED                        = 'project_aborted';
    const TEST_CASE                              = 'test_case';

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var string
     * @ORM\Column(name="reason", type="string", length=191, nullable=false)
     */
    private $reason;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=16777215, nullable=true)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_abandon", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idAbandon;

    /**
     * Set label
     *
     * @param string $label
     *
     * @return ProjectAbandonReason
     */
    public function setLabel(string $label): ProjectAbandonReason
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get idAbandon
     *
     * @return integer
     */
    public function getIdAbandon(): int
    {
        return $this->idAbandon;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     *
     * @return ProjectAbandonReason
     */
    public function setReason(string $reason): ProjectAbandonReason
    {
        $this->reason = $reason;

        return $this;
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
     * @return ProjectAbandonReason
     */
    public function setDescription(string $description): ProjectAbandonReason
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     *
     * @return ProjectAbandonReason
     */
    public function setStatus(bool $status): ProjectAbandonReason
    {
        $this->status = $status;

        return $this;
    }
}
