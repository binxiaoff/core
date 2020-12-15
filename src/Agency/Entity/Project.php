<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Exception;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

/**
 * @ApiResource(
 *     attributes={
 *         "route_prefix"="/agency"
 *     },
 *     normalizationContext={
 *         "groups": {
 *             "timestampable:read",
 *         }
 *     },
 *     collectionOperations={
 *         "get",
 *         "post": {
 *             "security": "is_granted('create', object)",
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *         },
 *     }
 * )
 *
 * @ORM\Table(name="agency_project")
 * @ORM\Entity
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Agency\Entity\Versioned\VersionedProject")
 */
class Project
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->added = new DateTimeImmutable();
    }
}
