<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Staff;

use Exception;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\ClientStatus;
use Unilend\Entity\TemporaryToken;
use Unilend\Message\Staff\StaffCreated;
use Unilend\Repository\StaffRepository;
use Unilend\Repository\TemporaryTokenRepository;
use Unilend\Service\Staff\StaffNotifier;

class StaffCreatedHandler implements MessageHandlerInterface
{
    /** @var StaffRepository */
    private $repository;
    /** @var StaffNotifier */
    private $notifier;
    /** @var TemporaryTokenRepository */
    private $temporaryTokenRepository;

    /**
     * @param StaffRepository          $staffRepository
     * @param StaffNotifier            $notifier
     * @param TemporaryTokenRepository $temporaryTokenRepository
     */
    public function __construct(
        StaffRepository $staffRepository,
        StaffNotifier $notifier,
        TemporaryTokenRepository $temporaryTokenRepository
    ) {
        $this->repository               = $staffRepository;
        $this->notifier                 = $notifier;
        $this->temporaryTokenRepository = $temporaryTokenRepository;
    }

    /**
     * @param StaffCreated $staffCreated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function __invoke(StaffCreated $staffCreated)
    {
        $staff = $this->repository->find($staffCreated->getStaffId());

        if ($staff) {
            $client = $staff->getClient();
            if (ClientStatus::STATUS_INVITED === $client->getCurrentStatus()->getStatus()) {
                $temporaryToken = TemporaryToken::generateMediumToken($client);
                $this->temporaryTokenRepository->save($temporaryToken);
                $this->notifier->notifyClientInitialisation($staff, $temporaryToken);
            }
        }
    }
}
