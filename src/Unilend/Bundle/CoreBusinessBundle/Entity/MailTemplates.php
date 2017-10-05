<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailTemplates
 *
 * @ORM\Table(name="mail_templates", indexes={@ORM\Index(name="type", columns={"type", "locale", "status", "part"}), @ORM\Index(name="fk_mail_templates_header", columns={"id_header"}), @ORM\Index(name="fk_mail_templates_footer", columns={"id_footer"})})
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity
 */
class MailTemplates
{
    const STATUS_ACTIVE   = 1;
    const STATUS_ARCHIVED = 2;

    const RECIPIENT_TYPE_INTERNAL = 'internal';
    const RECIPIENT_TYPE_EXTERNAL = 'external';

    const PART_TYPE_CONTENT = 'content';
    const PART_TYPE_HEADER  = 'header';
    const PART_TYPE_FOOTER  = 'footer';

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=191, nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=5, nullable=false)
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="part", type="string", length=30, nullable=false)
     */
    private $part;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_type", type="string", length=30, nullable=true)
     */
    private $recipientType;

    /**
     * @var string
     *
     * @ORM\Column(name="sender_name", type="string", length=191, nullable=true)
     */
    private $senderName;

    /**
     * @var string
     *
     * @ORM\Column(name="sender_email", type="string", length=191, nullable=true)
     */
    private $senderEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=191, nullable=true)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_header", referencedColumnName="id_mail_template")
     * })
     */
    private $idHeader;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_footer", referencedColumnName="id_mail_template")
     * })
     */
    private $idFooter;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_mail_template", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idMailTemplate;



    /**
     * Set type
     *
     * @param string $type
     *
     * @return MailTemplates
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set locale
     *
     * @param string $locale
     *
     * @return MailTemplates
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set part
     *
     * @param string $part
     *
     * @return MailTemplates
     */
    public function setPart($part)
    {
        $this->part = $part;

        return $this;
    }

    /**
     * Get part
     *
     * @return string
     */
    public function getPart()
    {
        return $this->part;
    }

    /**
     * Set recipientType
     *
     * @param string $recipientType
     *
     * @return MailTemplates
     */
    public function setRecipientType($recipientType)
    {
        $this->recipientType = $recipientType;

        return $this;
    }

    /**
     * Get recipientType
     *
     * @return string
     */
    public function getRecipientType()
    {
        return $this->recipientType;
    }

    /**
     * Set senderName
     *
     * @param string $senderName
     *
     * @return MailTemplates
     */
    public function setSenderName($senderName)
    {
        $this->senderName = $senderName;

        return $this;
    }

    /**
     * Get senderName
     *
     * @return string
     */
    public function getSenderName()
    {
        return $this->senderName;
    }

    /**
     * Set senderEmail
     *
     * @param string $senderEmail
     *
     * @return MailTemplates
     */
    public function setSenderEmail($senderEmail)
    {
        $this->senderEmail = $senderEmail;

        return $this;
    }

    /**
     * Get senderEmail
     *
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->senderEmail;
    }

    /**
     * Set subject
     *
     * @param string $subject
     *
     * @return MailTemplates
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return MailTemplates
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set idHeader
     *
     * @param MailTemplates $idHeader
     *
     * @return MailTemplates
     */
    public function setIdHeader($idHeader)
    {
        $this->idHeader = $idHeader;

        return $this;
    }

    /**
     * Get idHeader
     *
     * @return MailTemplates
     */
    public function getIdHeader()
    {
        return $this->idHeader;
    }

    /**
     * Set idFooter
     *
     * @param MailTemplates $idFooter
     *
     * @return MailTemplates
     */
    public function setIdFooter($idFooter)
    {
        $this->idFooter = $idFooter;

        return $this;
    }

    /**
     * Get idFooter
     *
     * @return MailTemplates
     */
    public function getIdFooter()
    {
        return $this->idFooter;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return MailTemplates
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return MailTemplates
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
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return MailTemplates
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idMailTemplate
     *
     * @return integer
     */
    public function getIdMailTemplate()
    {
        return $this->idMailTemplate;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
