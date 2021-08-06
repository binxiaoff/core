<?php

declare(strict_types=1);

namespace Unilend\Core\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class MessageInput
{
    /**
     * @Assert\NotBlank
     */
    public string $body;

    /**
     * @Assert\NotBlank
     */
    public string $entity;
}
