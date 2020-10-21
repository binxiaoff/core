<?php

declare(strict_types=1);

namespace Unilend\DTO;

class GoogleRecaptchaResult
{
    public ?float $score = null;

    public ?string $action = null;

    public bool $valid = false;
}
