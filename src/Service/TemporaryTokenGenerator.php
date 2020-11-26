<?php

declare(strict_types=1);

namespace Unilend\Service;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Entity\{TemporaryToken};
use Unilend\Repository\TemporaryTokenRepository;

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
     * @param Clients $client
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return TemporaryToken
     */
    public function generateMediumToken(Clients $client): TemporaryToken
    {
        $temporaryToken = TemporaryToken::generateMediumToken($client);

        $this->temporaryTokenRepository->save($temporaryToken);

        return $temporaryToken;
    }

    /**
     * @param Clients $client
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return TemporaryToken
     */
    public function generateUltraLongToken(Clients $client): TemporaryToken
    {
        $temporaryToken = TemporaryToken::generateUltraLongToken($client);

        $this->temporaryTokenRepository->save($temporaryToken);

        return $temporaryToken;
    }
}
