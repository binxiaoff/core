<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailjetEventBounce
 *
 * @ORM\Table(name="mailjet_event_bounce", indexes={@ORM\Index(name="email", columns={"email"}), @ORM\Index(name="custom_id", columns={"custom_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class MailjetEventBounce
{
    /**
     * @var int
     *
     * @ORM\Column(name="time", type="integer", nullable=false)
     */
    private $time;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=191, nullable=false)
     */
    private $email;

    /**
     * @var int
     *
     * @ORM\Column(name="campaign_id", type="integer", nullable=false)
     */
    private $campaignId;

    /**
     * @var int
     *
     * @ORM\Column(name="contact_id", type="integer", nullable=false)
     */
    private $contactId;

    /**
     * @var string
     *
     * @ORM\Column(name="custom_campaign", type="string", length=255, nullable=false)
     */
    private $customCampaign;

    /**
     * @var int
     *
     * @ORM\Column(name="message_id", type="integer", nullable=false)
     */
    private $messageId;

    /**
     * @var int
     *
     * @ORM\Column(name="custom_id", type="integer", nullable=true)
     */
    private $customId;

    /**
     * @var string
     *
     * @ORM\Column(name="payload", type="string", length=255, nullable=true)
     */
    private $payload;

    /**
     * @var int
     *
     * @ORM\Column(name="blocked", type="integer", nullable=false)
     */
    private $blocked;

    /**
     * @var int
     *
     * @ORM\Column(name="hard_bounce", type="integer", nullable=false)
     */
    private $hardBounce;

    /**
     * @var string
     *
     * @ORM\Column(name="error_related_to", type="string", length=32, nullable=false)
     */
    private $errorRelatedTo;

    /**
     * @var string
     *
     * @ORM\Column(name="error", type="string", length=191, nullable=false)
     */
    private $error;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


    /**
     * Set time
     *
     * @param int $time
     *
     * @return MailjetEventBounce
     */
    public function setTime(int $time): MailjetEventBounce
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time
     *
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return MailjetEventBounce
     */
    public function setEmail(string $email): MailjetEventBounce
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set campaignId
     *
     * @param int $campaignId
     *
     * @return MailjetEventBounce
     */
    public function setCampaignId(int $campaignId): MailjetEventBounce
    {
        $this->campaignId = $campaignId;

        return $this;
    }

    /**
     * Get campaignId
     *
     * @return int
     */
    public function getCampaignId(): int
    {
        return $this->campaignId;
    }

    /**
     * Set contactId
     *
     * @param int $contactId
     *
     * @return MailjetEventBounce
     */
    public function setContactId(int $contactId): MailjetEventBounce
    {
        $this->contactId = $contactId;

        return $this;
    }

    /**
     * Get contactId
     *
     * @return int
     */
    public function getContactId(): int
    {
        return $this->contactId;
    }

    /**
     * Set customCampaign
     *
     * @param string $customCampaign
     *
     * @return MailjetEventBounce
     */
    public function setCustomCampaign(string $customCampaign): MailjetEventBounce
    {
        $this->customCampaign = $customCampaign;

        return $this;
    }

    /**
     * Get customCampaign
     *
     * @return string
     */
    public function getCustomCampaign(): string
    {
        return $this->customCampaign;
    }

    /**
     * Set messageId
     *
     * @param int $messageId
     *
     * @return MailjetEventBounce
     */
    public function setMessageId(int $messageId): MailjetEventBounce
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * Get messageId
     *
     * @return int
     */
    public function getMessageId(): int
    {
        return $this->messageId;
    }

    /**
     * Set customId
     *
     * @param int|null $customId
     *
     * @return MailjetEventBounce
     */
    public function setCustomId(?int $customId): MailjetEventBounce
    {
        $this->customId = $customId;

        return $this;
    }

    /**
     * Get customId
     *
     * @return int|null
     */
    public function getCustomId(): ?int
    {
        return $this->customId;
    }

    /**
     * Set payload
     *
     * @param string|null $payload
     *
     * @return MailjetEventBounce
     */
    public function setPayload(?string $payload): MailjetEventBounce
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Get payload
     *
     * @return string|null
     */
    public function getPayload(): ?string
    {
        return $this->payload;
    }

    /**
     * Set blocked
     *
     * @param int $blocked
     *
     * @return MailjetEventBounce
     */
    public function setBlocked(int $blocked): MailjetEventBounce
    {
        $this->blocked = $blocked;

        return $this;
    }

    /**
     * Get blocked
     *
     * @return int
     */
    public function getBlocked(): int
    {
        return $this->blocked;
    }

    /**
     * Set hardBounce
     *
     * @param int $hardBounce
     *
     * @return MailjetEventBounce
     */
    public function setHardBounce(int $hardBounce): MailjetEventBounce
    {
        $this->hardBounce = $hardBounce;

        return $this;
    }

    /**
     * Get hardBounce
     *
     * @return int
     */
    public function getHardBounce(): int
    {
        return $this->hardBounce;
    }

    /**
     * Set errorRelatedTo
     *
     * @param string $errorRelatedTo
     *
     * @return MailjetEventBounce
     */
    public function setErrorRelatedTo(string $errorRelatedTo): MailjetEventBounce
    {
        $this->errorRelatedTo = $errorRelatedTo;

        return $this;
    }

    /**
     * Get errorRelatedTo
     *
     * @return string
     */
    public function getErrorRelatedTo(): string
    {
        return $this->errorRelatedTo;
    }

    /**
     * Set error
     *
     * @param string $error
     *
     * @return MailjetEventBounce
     */
    public function setError(string $error): MailjetEventBounce
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Get error
     *
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return MailjetEventBounce
     */
    public function setAdded(\DateTime $added): MailjetEventBounce
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }
}
