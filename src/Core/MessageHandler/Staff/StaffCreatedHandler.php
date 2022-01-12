<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler\Staff;

use Exception;
use KLS\Core\Message\Staff\StaffCreated;
use KLS\Core\Repository\StaffRepository;
use KLS\Core\Service\Staff\StaffNotifier;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class StaffCreatedHandler implements MessageHandlerInterface
{
    private StaffRepository $staffRepository;
    private StaffNotifier $notifier;

    public function __construct(StaffRepository $staffRepository, StaffNotifier $notifier)
    {
        $this->staffRepository = $staffRepository;
        $this->notifier        = $notifier;
    }

    /**
     * @throws Exception
     */
    public function __invoke(StaffCreated $staffCreated): void
    {
        $staff = $this->staffRepository->find($staffCreated->getStaffId());
        if ($staff) {
            // TODO Remove when this become asynchronous
            // Refresh the staff, so that the $user->getStaff() doesn't return null
            $this->staffRepository->refresh($staff);
            $this->notifier->notifyUserInitialisation($staff);
        }
    }
}
