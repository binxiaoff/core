<?php

declare(strict_types=1);

namespace Unilend\Core\Service;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Unilend\Core\Entity\User;
use Unilend\Core\Entity\{TemporaryToken};
use Unilend\Core\Repository\TemporaryTokenRepository;

class TemporaryTokenGenerator
{
    /** @var TemporaryTokenRepository */
    private $temporaryTokenRepository;

    /**
     * @param TemporaryTokenRepository $temporaryTokenRepository
     */
    public function __construct(TemporaryTokenRepository $temporaryTokenRepository)
    {
        $this->temporaryTokenRepository = $temporaryTokenRepository;
    }

    /**
     * @param User $user
     *
     * @return TemporaryToken
     *
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @throws ORMException
     */
    public function generateMediumToken(User $user): TemporaryToken
    {
        $temporaryToken = TemporaryToken::generateMediumToken($user);

        $this->temporaryTokenRepository->save($temporaryToken);

        return $temporaryToken;
    }

    /**
     * @param User $user
     *
     * @return TemporaryToken
     *
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @throws ORMException
     */
    public function generateUltraLongToken(User $user): TemporaryToken
    {
        $temporaryToken = TemporaryToken::generateUltraLongToken($user);

        $this->temporaryTokenRepository->save($temporaryToken);

        return $temporaryToken;
    }
}
