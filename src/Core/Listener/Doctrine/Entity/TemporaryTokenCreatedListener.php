<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity;

use Exception;
use KLS\Core\Entity\TemporaryToken;
use KLS\Core\Repository\TemporaryTokenRepository;

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
