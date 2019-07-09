<?php

declare(strict_types=1);

namespace Unilend\Message\ServiceTerms;

class ServiceTermsAccepted
{
    /** @var int */
    private $acceptationId;

    /**
     * @param int $acceptationId
     */
    public function __construct(int $acceptationId)
    {
        $this->acceptationId = $acceptationId;
    }

    /**
     * @return int
     */
    public function getAcceptationId(): int
    {
        return $this->acceptationId;
    }
}
