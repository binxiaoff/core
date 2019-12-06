<?php

declare(strict_types=1);

namespace Unilend\Entity\Request;

use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "post": {
 *             "controller": "Unilend\Controller\Captcha\Check",
 *         }
 *     },
 *     itemOperations={},
 *     output=false
 * )
 */
class CaptchaCheck
{
    public $captchaValue;
}
