<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\MappedSuperclass
 *
 * @Gedmo\SoftDeleteable(fieldName="archived", hardDelete=false)
 */
abstract class AbstractMailPart
{
    use TimestampableTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer")
     */
    private $id;

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
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var bool
     *
     * @ORM\Column(name="archived", type="datetime", nullable=true)
     */
    private $archived;

    /**
     * MailPart constructor.
     *
     * @param string $type
     * @param string $locale
     */
    public function __construct(
        string $type,
        string $locale = 'fr_FR'
    ) {
        $this->archived = false;
        $this->type     = $type;
        $this->locale   = $locale;
    }

    /**
     * @param string $type
     *
     * @return MailHeader
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $content
     *
     * @return MailHeader
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param bool $archived
     *
     * @return MailHeader
     */
    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return bool
     */
    public function getArchived(): bool
    {
        return $this->archived;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return MailHeader
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
