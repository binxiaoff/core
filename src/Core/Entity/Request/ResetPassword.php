<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Request;

use ApiPlatform\Core\Annotation\ApiResource;

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
class ResetPassword
{
    /** @var string */
    public $email;

    /** @var string */
    public $captchaValue;
}
