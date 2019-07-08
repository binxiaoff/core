<?php

declare(strict_types=1);

namespace Unilend\Message\TermsOfSale;

class TermsOfSaleAccepted
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
