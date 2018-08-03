<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailjetEventSpam
 *
 * @ORM\Table(name="mailjet_event_spam", indexes={@ORM\Index(name="email", columns={"email"}), @ORM\Index(name="custom_id", columns={"custom_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class MailjetEventSpam
{
    /**
     *@var int
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
     *@var int
     *
     * @ORM\Column(name="campaign_id", type="integer", nullable=false)
     */
    private $campaignId;

    /**
     *@var int
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
     *@var int
     *
     * @ORM\Column(name="message_id", type="integer", nullable=false)
     */
    private $messageId;

    /**
     *@var int
     *
     * @ORM\Column(name="custom_id", type="integer", nullable=true)
     */
    private $customId;

    /**
     * @var string
     *
     * @ORM\Column(name="payload", type="string", length=255, nullable=false)
     */
    private $payload;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=191, nullable=false)
     */
    private $source;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     *@var int
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
     * @return MailjetEventSpam
     */
    public function setTime(int $time): MailjetEventSpam
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
     * @return MailjetEventSpam
     */
    public function setEmail(string $email): MailjetEventSpam
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
     * @return MailjetEventSpam
     */
    public function setCampaignId(int $campaignId): MailjetEventSpam
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
     * @return MailjetEventSpam
     */
    public function setContactId(int $contactId): MailjetEventSpam
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
     * @return MailjetEventSpam
     */
    public function setCustomCampaign(string $customCampaign): MailjetEventSpam
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
     * @return MailjetEventSpam
     */
    public function setMessageId(int $messageId): MailjetEventSpam
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
     * @return MailjetEventSpam
     */
    public function setCustomId(?int $customId): MailjetEventSpam
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
     * @param string $payload
     *
     * @return MailjetEventSpam
     */
    public function setPayload(string $payload): MailjetEventSpam
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Get payload
     *
     * @return string
     */
    public function getPayload(): string
    {
        return $this->payload;
    }

    /**
     * Set source
     *
     * @param string $source
     *
     * @return MailjetEventSpam
     */
    public function setSource(string $source): MailjetEventSpam
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return MailjetEventSpam
     */
    public function setAdded(\DateTime $added): MailjetEventSpam
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
