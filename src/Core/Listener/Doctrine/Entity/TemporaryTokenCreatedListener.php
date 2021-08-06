<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Entity;

use Exception;
use Unilend\Core\Entity\TemporaryToken;
use Unilend\Core\Repository\TemporaryTokenRepository;

class TemporaryTokenCreatedListener
{
    /**
     * @var TemporaryTokenRepository
     */
    private $repository;

    public function __construct(
        TemporaryTokenRepository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * @throws Exception
     */
    public function expireOldTemporaryToken(TemporaryToken $token): void
    {
        $this->repository->expireTemporaryTokens($token->getUser());
    }
}
