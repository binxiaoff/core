<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ApiResource(
 *     itemOperations={"get"}
 * )
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class LegalDocument
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    public const TYPE_SERVICE_TERMS = 1;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(length=191)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=16777215)
     */
    private $firstTimeInstruction;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=16777215)
     */
    private $differentialInstruction;

    /**
     * LegalDocument constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->added = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return LegalDocument
     */
    public function setType(int $type): LegalDocument
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return LegalDocument
     */
    public function setTitle(string $title): LegalDocument
    {
        $this->title = $title;

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
     * @param string $content
     *
     * @return LegalDocument
     */
    public function setContent(string $content): LegalDocument
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstTimeInstruction(): string
    {
        return $this->firstTimeInstruction;
    }

    /**
     * @param string $firstTimeInstruction
     *
     * @return LegalDocument
     */
    public function setFirstTimeInstruction(string $firstTimeInstruction): LegalDocument
    {
        $this->firstTimeInstruction = $firstTimeInstruction;

        return $this;
    }

    /**
     * @return string
     */
    public function getDifferentialInstruction(): string
    {
        return $this->differentialInstruction;
    }

    /**
     * @param string $differentialInstruction
     *
     * @return LegalDocument
     */
    public function setDifferentialInstruction(string $differentialInstruction): LegalDocument
    {
        $this->differentialInstruction = $differentialInstruction;

        return $this;
    }
}
