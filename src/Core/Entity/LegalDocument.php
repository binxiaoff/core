<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;

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
 *             "controller": "Unilend\Core\Controller\LegalDocument\CurrentServiceTerms",
 *             "path": "/core/legal_documents/current_service_terms",
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="core_legal_document")
 */
class LegalDocument
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    public const TYPE_SERVICE_TERMS = 1;

    // TODO CALS-4049 Should not use id here
    public const CURRENT_SERVICE_TERMS_ID = 3;

    /**
     * @ORM\Column(type="smallint")
     */
    private int $type;

    /**
     * @ORM\Column(length=191)
     *
     * @Groups({"legalDocument:read"})
     */
    private string $title;

    /**
     * @ORM\Column(type="text")
     *
     * @Groups({"legalDocument:read"})
     */
    private string $content;

    /**
     * @throws Exception
     */
    public function __construct(int $type, string $title, string $content)
    {
        $this->added   = new DateTimeImmutable();
        $this->type    = $type;
        $this->title   = $title;
        $this->content = $content;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
