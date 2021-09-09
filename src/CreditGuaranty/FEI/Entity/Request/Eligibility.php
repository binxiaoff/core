<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity\Request;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use KLS\CreditGuaranty\FEI\Controller\EligibilityChecking;
use KLS\CreditGuaranty\FEI\Entity\Reservation;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "post_checking": {
 *             "method": "POST",
 *             "path": "/credit_guaranty/eligibilities/checking",
 *             "controller": EligibilityChecking::class,
 *             "security_post_denormalize": "is_granted('create', object)",
 *         },
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         },
 *     },
 * )
 */
class Eligibility
{
    public Reservation $reservation;

    public ?string $category = null;

    public bool $withConditions = false;

    public array $ineligibles = [];

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
