<?php

declare(strict_types=1);

namespace Unilend\Entity\Messenger;

use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(
 *     messenger=true,
 *     collectionOperations={
 *         "post": {"status": 202}
 *     },
 *     itemOperations={},
 *     output=false
 * )
 */
class ResetPassword
{
    public $email;
}
