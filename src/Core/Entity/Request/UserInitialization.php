<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Request;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     messenger=true,
 *     collectionOperations={
 *         "post": {"status": 202},
 *     },
 *     itemOperations={},
 *     output=false,
 * )
 */
class UserInitialization
{
    /**
     * @Assert\Email
     */
    public string $email;
}
