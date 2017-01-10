<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailjetEventSpam
 *
 * @ORM\Table(name="mailjet_event_spam", indexes={@ORM\Index(name="email", columns={"email"}), @ORM\Index(name="custom_id", columns={"custom_id"})})
 * @ORM\Entity
 */
class MailjetEventSpam
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
     * @return MailjetEventSpam
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
     * @return MailjetEventSpam
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
     * @return MailjetEventSpam
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
     * @return MailjetEventSpam
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
     * @return MailjetEventSpam
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
     * @return MailjetEventSpam
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
     * @return MailjetEventSpam
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
     * @return MailjetEventSpam
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
     * Set source
     *
     * @param string $source
     *
     * @return MailjetEventSpam
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
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
