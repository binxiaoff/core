<?php

declare(strict_types=1);

namespace KLS\Core\Service;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use KLS\Core\Entity\User;
use KLS\Core\Entity\TemporaryToken;
use KLS\Core\Repository\TemporaryTokenRepository;

class TemporaryTokenGenerator
{
    /** @var TemporaryTokenRepository */
    private $temporaryTokenRepository;

    public function __construct(TemporaryTokenRepository $temporaryTokenRepository)
    {
        $this->temporaryTokenRepository = $temporaryTokenRepository;
    }

    /**
     * @throws OptimisticLockException
     * @throws Exception
     * @throws ORMException
     */
    public function generateMediumToken(User $user): TemporaryToken
    {
        $temporaryToken = TemporaryToken::generateMediumToken($user);

        $this->temporaryTokenRepository->save($temporaryToken);

        return $temporaryToken;
    }

    /**
     * @throws OptimisticLockException
     * @throws Exception
     * @throws ORMException
     */
    public function generateUltraLongToken(User $user): TemporaryToken
    {
        $temporaryToken = TemporaryToken::generateUltraLongToken($user);

        $this->temporaryTokenRepository->save($temporaryToken);

        return $temporaryToken;
    }
}
