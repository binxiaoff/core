<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailjetEventUnsub
 *
 * @ORM\Table(name="mailjet_event_unsub", indexes={@ORM\Index(name="email", columns={"email"}), @ORM\Index(name="custom_id", columns={"custom_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class MailjetEventUnsub
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
     * @ORM\Column(name="list_id", type="integer", nullable=false)
     */
    private $listId;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=39, nullable=false)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="geo", type="string", length=2, nullable=false)
     */
    private $geo;

    /**
     * @var string
     *
     * @ORM\Column(name="agent", type="string", length=255, nullable=false)
     */
    private $agent;

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
     * @return MailjetEventUnsub
     */
    public function setTime(int $time): MailjetEventUnsub
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
     * @return MailjetEventUnsub
     */
    public function setEmail(string $email): MailjetEventUnsub
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
     * @return MailjetEventUnsub
     */
    public function setCampaignId(int $campaignId): MailjetEventUnsub
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
     * @return MailjetEventUnsub
     */
    public function setContactId(int $contactId): MailjetEventUnsub
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
     * @return MailjetEventUnsub
     */
    public function setCustomCampaign(string $customCampaign): MailjetEventUnsub
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
     * @return MailjetEventUnsub
     */
    public function setMessageId(int $messageId): MailjetEventUnsub
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
     * @return MailjetEventUnsub
     */
    public function setCustomId(?int $customId): MailjetEventUnsub
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
     * @return MailjetEventUnsub
     */
    public function setPayload(?string $payload): MailjetEventUnsub
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
     * Set listId
     *
     * @param int $listId
     *
     * @return MailjetEventUnsub
     */
    public function setListId(int $listId): MailjetEventUnsub
    {
        $this->listId = $listId;

        return $this;
    }

    /**
     * Get listId
     *
     * @return int
     */
    public function getListId(): int
    {
        return $this->listId;
    }

    /**
     * Set ip
     *
     * @param string $ip
     *
     * @return MailjetEventUnsub
     */
    public function setIp(string $ip): MailjetEventUnsub
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Set geo
     *
     * @param string $geo
     *
     * @return MailjetEventUnsub
     */
    public function setGeo(string $geo): MailjetEventUnsub
    {
        $this->geo = $geo;

        return $this;
    }

    /**
     * Get geo
     *
     * @return string
     */
    public function getGeo(): string
    {
        return $this->geo;
    }

    /**
     * Set agent
     *
     * @param string $agent
     *
     * @return MailjetEventUnsub
     */
    public function setAgent(string $agent): MailjetEventUnsub
    {
        $this->agent = $agent;

        return $this;
    }

    /**
     * Get agent
     *
     * @return string
     */
    public function getAgent(): string
    {
        return $this->agent;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return MailjetEventUnsub
     */
    public function setAdded(\DateTime $added): MailjetEventUnsub
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
