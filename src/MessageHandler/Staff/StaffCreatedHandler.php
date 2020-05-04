<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Staff;

use Exception;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\Staff\StaffCreated;
use Unilend\Repository\StaffRepository;
use Unilend\Service\Staff\StaffNotifier;

class StaffCreatedHandler implements MessageHandlerInterface
{
    /** @var StaffRepository */
    private $staffRepository;
    /** @var StaffNotifier */
    private $notifier;

    /**
     * @param StaffRepository $staffRepository
     * @param StaffNotifier   $notifier
     */
    public function __construct(StaffRepository $staffRepository, StaffNotifier $notifier)
    {
        $this->staffRepository = $staffRepository;
        $this->notifier        = $notifier;
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
            // TODO Remove when this become asynchronous
            // Refresh the staff, so that the $client->getStaff() doesn't return null
            $this->staffRepository->refresh($staff);
            $this->notifier->notifyClientInitialisation($staff);
        }
    }
}
