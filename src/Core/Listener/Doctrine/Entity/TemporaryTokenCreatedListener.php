<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Entity;

use Exception;
use Unilend\Core\Entity\TemporaryToken;
use Unilend\Repository\TemporaryTokenRepository;

class TemporaryTokenCreatedListener
{
    /**
     * @var TemporaryTokenRepository
     */
    private $repository;

    /**
     * @param TemporaryTokenRepository $repository
     */
    public function __construct(
        TemporaryTokenRepository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * @param TemporaryToken $token
     *
     * @throws Exception
     */
    public function expireOldTemporaryToken(TemporaryToken $token): void
    {
        $this->repository->expireTemporaryTokens($token->getClient());
    }
}
