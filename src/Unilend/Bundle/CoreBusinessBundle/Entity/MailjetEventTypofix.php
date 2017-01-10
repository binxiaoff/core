<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailjetEventTypofix
 *
 * @ORM\Table(name="mailjet_event_typofix", indexes={@ORM\Index(name="email", columns={"email"}), @ORM\Index(name="custom_id", columns={"custom_id"}), @ORM\Index(name="original_address", columns={"original_address"}), @ORM\Index(name="new_address", columns={"new_address"})})
 * @ORM\Entity
 */
class MailjetEventTypofix
{
    /**
     * @var integer
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
     * @var integer
     *
     * @ORM\Column(name="campaign_id", type="integer", nullable=false)
     */
    private $campaignId;

    /**
     * @var integer
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
     * @var integer
     *
     * @ORM\Column(name="message_id", type="integer", nullable=false)
     */
    private $messageId;

    /**
     * @var integer
     *
     * @ORM\Column(name="custom_id", type="integer", nullable=false)
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
     * @ORM\Column(name="original_address", type="string", length=191, nullable=false)
     */
    private $originalAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="new_address", type="string", length=191, nullable=false)
     */
    private $newAddress;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Set time
     *
     * @param integer $time
     *
     * @return MailjetEventTypofix
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time
     *
     * @return integer
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return MailjetEventTypofix
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set campaignId
     *
     * @param integer $campaignId
     *
     * @return MailjetEventTypofix
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;

        return $this;
    }

    /**
     * Get campaignId
     *
     * @return integer
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * Set contactId
     *
     * @param integer $contactId
     *
     * @return MailjetEventTypofix
     */
    public function setContactId($contactId)
    {
        $this->contactId = $contactId;

        return $this;
    }

    /**
     * Get contactId
     *
     * @return integer
     */
    public function getContactId()
    {
        return $this->contactId;
    }

    /**
     * Set customCampaign
     *
     * @param string $customCampaign
     *
     * @return MailjetEventTypofix
     */
    public function setCustomCampaign($customCampaign)
    {
        $this->customCampaign = $customCampaign;

        return $this;
    }

    /**
     * Get customCampaign
     *
     * @return string
     */
    public function getCustomCampaign()
    {
        return $this->customCampaign;
    }

    /**
     * Set messageId
     *
     * @param integer $messageId
     *
     * @return MailjetEventTypofix
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * Get messageId
     *
     * @return integer
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Set customId
     *
     * @param integer $customId
     *
     * @return MailjetEventTypofix
     */
    public function setCustomId($customId)
    {
        $this->customId = $customId;

        return $this;
    }

    /**
     * Get customId
     *
     * @return integer
     */
    public function getCustomId()
    {
        return $this->customId;
    }

    /**
     * Set payload
     *
     * @param string $payload
     *
     * @return MailjetEventTypofix
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Get payload
     *
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Set originalAddress
     *
     * @param string $originalAddress
     *
     * @return MailjetEventTypofix
     */
    public function setOriginalAddress($originalAddress)
    {
        $this->originalAddress = $originalAddress;

        return $this;
    }

    /**
     * Get originalAddress
     *
     * @return string
     */
    public function getOriginalAddress()
    {
        return $this->originalAddress;
    }

    /**
     * Set newAddress
     *
     * @param string $newAddress
     *
     * @return MailjetEventTypofix
     */
    public function setNewAddress($newAddress)
    {
        $this->newAddress = $newAddress;

        return $this;
    }

    /**
     * Get newAddress
     *
     * @return string
     */
    public function getNewAddress()
    {
        return $this->newAddress;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return MailjetEventTypofix
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
