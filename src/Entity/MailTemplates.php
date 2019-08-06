<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="mail_templates", indexes={@ORM\Index(name="type", columns={"type", "locale", "status", "part"}), @ORM\Index(name="fk_mail_templates_header", columns={"id_header"}), @ORM\Index(name="fk_mail_templates_footer", columns={"id_footer"})})
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity
 */
class MailTemplates
{
    public const STATUS_ACTIVE   = 1;
    public const STATUS_ARCHIVED = 2;

    public const RECIPIENT_TYPE_INTERNAL = 'internal';
    public const RECIPIENT_TYPE_EXTERNAL = 'external';

    public const PART_TYPE_CONTENT = 'content';
    public const PART_TYPE_HEADER  = 'header';
    public const PART_TYPE_FOOTER  = 'footer';

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=191)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=5)
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="part", type="string", length=30)
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
     * @var string
     *
     * @ORM\Column(name="compiled_content", type="text", nullable=true)
     */
    private $compiledContent;

    /**
     * @var \Unilend\Entity\MailTemplates
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\MailTemplates")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_header", referencedColumnName="id_mail_template")
     * })
     */
    private $idHeader;

    /**
     * @var \Unilend\Entity\MailTemplates
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\MailTemplates")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_footer", referencedColumnName="id_mail_template")
     * })
     */
    private $idFooter;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_mail_template", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idMailTemplate;

    /**
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
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
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
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
     * @return string
     */
    public function getPart()
    {
        return $this->part;
    }

    /**
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
     * @return string
     */
    public function getRecipientType()
    {
        return $this->recipientType;
    }

    /**
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
     * @return string
     */
    public function getSenderName()
    {
        return $this->senderName;
    }

    /**
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
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->senderEmail;
    }

    /**
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
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
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
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $compiledContent
     *
     * @return MailTemplates
     */
    public function setCompiledContent($compiledContent)
    {
        $this->compiledContent = $compiledContent;

        return $this;
    }

    /**
     * @return string
     */
    public function getCompiledContent()
    {
        return $this->compiledContent;
    }

    /**
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
     * @return MailTemplates
     */
    public function getIdHeader()
    {
        return $this->idHeader;
    }

    /**
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
     * @return MailTemplates
     */
    public function getIdFooter()
    {
        return $this->idFooter;
    }

    /**
     * @param int $status
     *
     * @return MailTemplates
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
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
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
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
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @return int
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
        if (!$this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
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
