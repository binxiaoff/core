<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class MailTemplate.
 *
 * @ORM\Entity
 */
class MailTemplate extends AbstractMailPart
{
    /**
     * @var MailHeader
     *
     * @ORM\ManyToOne(targetEntity="MailHeader", fetch="EAGER")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_header")
     * })
     */
    private $header;

    /**
     * @var MailFooter
     *
     * @ORM\ManyToOne(targetEntity="MailFooter", fetch="EAGER")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_footer")
     * })
     */
    private $footer;

    /**
     * @var MailLayout
     *
     * @ORM\ManyToOne(targetEntity="MailLayout", fetch="EAGER")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_layout")
     * })
     */
    private $layout;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=191, nullable=true)
     */
    private $subject;

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
     * MailTemplate constructor.
     *
     * @param string     $type
     * @param MailLayout $layout
     * @param string     $locale
     */
    public function __construct(string $type, MailLayout $layout, string $locale = 'fr_FR')
    {
        parent::__construct($type, $locale);
        $this->layout = $layout;
    }

    /**
     * @return MailHeader|null
     */
    public function getHeader(): ?MailHeader
    {
        return $this->header;
    }

    /**
     * @param MailHeader|null $header
     *
     * @return MailTemplate
     */
    public function setHeader(?MailHeader $header): MailTemplate
    {
        $this->header = $header;

        return $this;
    }

    /**
     * @return MailFooter|null
     */
    public function getFooter(): ?MailFooter
    {
        return $this->footer;
    }

    /**
     * @param MailFooter|null $footer
     *
     * @return MailTemplate
     */
    public function setFooter(?MailFooter $footer): MailTemplate
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @param string|null $subject
     *
     * @return MailTemplate
     */
    public function setSubject(?string $subject): MailTemplate
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    /**
     * @param string|null $senderName
     *
     * @return MailTemplate
     */
    public function setSenderName(?string $senderName): MailTemplate
    {
        $this->senderName = $senderName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    /**
     * @param string|null $senderEmail
     *
     * @return MailTemplate
     */
    public function setSenderEmail(?string $senderEmail): MailTemplate
    {
        $this->senderEmail = $senderEmail;

        return $this;
    }

    /**
     * @return MailLayout
     */
    public function getLayout(): MailLayout
    {
        return $this->layout;
    }

    /**
     * @param MailLayout $layout
     *
     * @return MailTemplate
     */
    public function setLayout(MailLayout $layout): MailTemplate
    {
        $this->layout = $layout;

        return $this;
    }
}
