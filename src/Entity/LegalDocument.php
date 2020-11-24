<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"legalDocument:read", "timestampable:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     },
 *     collectionOperations={
 *         "current_service_terms": {
 *             "method": "GET",
 *             "controller": "Unilend\Controller\LegalDocument\CurrentServiceTerms",
 *             "path": "/legal_documents/current_service_terms",
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class LegalDocument
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    public const TYPE_SERVICE_TERMS = 1;

    public const CURRENT_SERVICE_TERMS = 2;

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
     *
     * @Groups({"legalDocument:read"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     *
     * @Groups({"legalDocument:read"})
     */
    private $content;

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
}
