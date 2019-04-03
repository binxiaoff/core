<?php

namespace Unilend\Entity;

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
    const BORROWER_FOLLOW_UP_UNSUCCESSFUL        = 'borrower_follow_up_unsuccessful';

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false, unique=true)
     */
    private $label;

    /**
     * @var string
     * @ORM\Column(name="reason", type="string", length=191)
     */
    private $reason;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=16777215, nullable=true)
     */
    private $description;

    /**
     * @var bool
     *
     * @ORM\Column(name="status", type="boolean")
     */
    private $status;

    /**
     * @var int
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
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return ProjectAbandonReason
     */
    public function setDescription(?string $description): ProjectAbandonReason
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
