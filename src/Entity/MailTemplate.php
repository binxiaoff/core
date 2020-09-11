<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Twig\Source;
use Unilend\Entity\Interfaces\TwigTemplateInterface;
use Unilend\Entity\Traits\MailPartTrait;

/**
 * Class MailTemplate.
 *
 * @ORM\Entity(repositoryClass="Unilend\Repository\MailTemplateRepository")
 * @ORM\HasLifecycleCallbacks
 */
class MailTemplate implements TwigTemplateInterface
{
    use MailPartTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer")
     */
    private int $id;

    /**
     * @var MailHeader|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\MailHeader", fetch="EAGER")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_header")
     * })
     */
    private ?MailHeader $header;

    /**
     * @var MailFooter|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\MailFooter", fetch="EAGER")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_footer")
     * })
     */
    private ?MailFooter $footer;

    /**
     * @var MailLayout
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\MailLayout", fetch="EAGER")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_layout")
     * })
     */
    private MailLayout $layout;

    /**
     * @var string|null
     *
     * @ORM\Column(name="subject", type="string", length=191, nullable=true)
     */
    private ?string $subject;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sender_name", type="string", length=191, nullable=true)
     */
    private ?string $senderName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sender_email", type="string", length=191, nullable=true)
     */
    private ?string $senderEmail;

    /**
     * @param string     $name
     * @param MailLayout $layout
     * @param string     $locale
     */
    public function __construct(string $name, MailLayout $layout, string $locale = 'fr_FR')
    {
        $this->name   = $name;
        $this->locale = $locale;
        $this->layout = $layout;
        $this->added = new DateTimeImmutable();
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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Source
     */
    public function getSource(): Source
    {
        $sourceCode = $this->getLayout()->getContent();

        $parts = [
            'header' => ($mailHeader = $this->getHeader()) ? $mailHeader->getContent() : null,
            'body'   => $this->content,
            'footer' => ($mailFooter = $this->getFooter()) ? $mailFooter->getContent() : null,
        ];

        $parts = array_filter($parts);

        foreach ($parts as $part => $content) {
            $content    = "{% block {$part} -%} " . $content . " {%- endblock {$part} %}";
            $sourceCode = preg_replace("/{%\\s*block\\s+{$part}\\s*%}.*{%\\s*endblock(?:\\s*|\\s+{$part}\\s*)%}/misU", $content, $sourceCode);
        }

        return new Source($sourceCode, $this->getName());
    }
}
