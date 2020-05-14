<?php

declare(strict_types=1);

namespace Unilend\Message\ServiceTerms;

use Unilend\Entity\AcceptationsLegalDocs;
use Unilend\Message\AsyncMessageInterface;

class ServiceTermsAccepted implements AsyncMessageInterface
{
    /** @var int */
    private $acceptationId;

    /**
     * @param AcceptationsLegalDocs $acceptation
     */
    public function __construct(AcceptationsLegalDocs $acceptation)
    {
        $this->acceptationId = $acceptation->getIdAcceptation();
    }

    /**
     * @return int
     */
    public function getAcceptationId(): int
    {
        return $this->acceptationId;
    }
}
