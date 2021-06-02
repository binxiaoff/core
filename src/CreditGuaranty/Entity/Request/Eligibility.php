<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity\Request;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\CreditGuaranty\Controller\EligibilityChecking;
use Unilend\CreditGuaranty\Entity\Reservation;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "post_checking": {
 *             "method": "POST",
 *             "path": "/credit_guaranty/eligibility/checking",
 *             "controller": EligibilityChecking::class,
 *             "security_post_denormalize": "is_granted('edit', object)",
 *             "denormalization_context": {"groups": {"creditGuaranty:eligibility:write"}}
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     }
 * )
 */
class Eligibility
{
    /**
     * @Groups({"creditGuaranty:eligibility:write"})
     */
    public Reservation $reservation;

    /**
     * @Groups({"creditGuaranty:eligibility:write"})
     */
    public string $category;

    public bool $eligible = false;

    /**
     * API Platform need an identifier to show the result of POST. We add here a fake id.
     *
     * @ApiProperty(identifier=true)
     */
    public function getId(): string
    {
        return 'not_an_id';
    }
}
