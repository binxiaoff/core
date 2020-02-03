<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Staff;

use Exception;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\TemporaryToken;
use Unilend\Message\Staff\StaffCreated;
use Unilend\Repository\{StaffRepository, TemporaryTokenRepository};
use Unilend\Service\Staff\StaffNotifier;

class StaffCreatedHandler implements MessageHandlerInterface
{
    /** @var StaffRepository */
    private $staffRepository;
    /** @var StaffNotifier */
    private $notifier;
    /** @var TemporaryTokenRepository */
    private $temporaryTokenRepository;

    /**
     * @param StaffRepository          $staffRepository
     * @param StaffNotifier            $notifier
     * @param TemporaryTokenRepository $temporaryTokenRepository
     */
    public function __construct(StaffRepository $staffRepository, StaffNotifier $notifier, TemporaryTokenRepository $temporaryTokenRepository)
    {
        $this->staffRepository          = $staffRepository;
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
        $staff = $this->staffRepository->find($staffCreated->getStaffId());
        if ($staff) {
            // Refresh the staff, so that the $client->getStaff() doesn't return null
            $this->staffRepository->refresh($staff);
            $client = $staff->getClient();
            if ($client->isInvited() && $client->isGrantedLogin()) {
                $temporaryToken = TemporaryToken::generateMediumToken($client);
                $this->temporaryTokenRepository->save($temporaryToken);
                $this->notifier->notifyClientInitialisation($staff, $temporaryToken);
            }
        }
    }
}
