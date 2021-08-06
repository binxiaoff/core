<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Traits;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups; // Necessary @see https://github.com/doctrine/annotations/issues/81
use Twig\Source;

/**
 * @Gedmo\SoftDeleteable(fieldName="archived", hardDelete=false)
 */
trait MailPartTrait
{
    use TimestampableTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191)
     *
     * @Groups({"mailPart:read"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=5)
     *
     * @Groups({"mailPart:read"})
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     *
     * @Groups({"mailPart:read"})
     */
    private $content;

    /**
     * @var bool
     *
     * @ORM\Column(name="archived", type="datetime", nullable=true)
     *
     * @Groups({"mailPart:read"})
     */
    private $archived;

    /**
     * @throws Exception
     */
    public function __construct(
        string $name,
        string $locale = 'fr_FR'
    ) {
        $this->name   = $name;
        $this->locale = $locale;
        $this->added  = new DateTimeImmutable();
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;

        return $this;
    }

    public function getArchived(): bool
    {
        return $this->archived;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getSource(): Source
    {
        return new Source($this->content, $this->getName());
    }
}
