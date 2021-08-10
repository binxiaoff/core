<?php

declare(strict_types=1);

namespace KLS\Core\DTO;

class GoogleRecaptchaResult
{
    public ?float $score = null;

    public ?string $action = null;

    public bool $valid = false;
}
