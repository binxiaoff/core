<?php

declare(strict_types=1);

namespace Unilend\Listener\Entity;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Exception;
use Unilend\Entity\TemporaryToken;
use Unilend\Repository\TemporaryTokenRepository;

class TemporaryTokenListener
{
    /**
     * @var TemporaryTokenRepository
     */
    private $repository;

    /**
     * TemporaryTokenListener constructor.
     *
     * @param TemporaryTokenRepository $repository
     */
    public function __construct(
        TemporaryTokenRepository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * @param TemporaryToken     $token
     * @param LifecycleEventArgs $eventArgs
     *
     * @throws Exception
     */
    public function prePersist(TemporaryToken $token, LifecycleEventArgs $eventArgs): void
    {
        $this->repository->expireTemporaryTokens($token->getClient());
    }
}
