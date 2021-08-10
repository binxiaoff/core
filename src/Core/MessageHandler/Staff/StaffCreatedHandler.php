<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler\Staff;

use Exception;
use KLS\Core\Message\Staff\StaffCreated;
use KLS\Core\Repository\StaffRepository;
use KLS\Core\Service\Staff\StaffNotifier;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class StaffCreatedHandler implements MessageHandlerInterface
{
    /** @var StaffRepository */
    private $staffRepository;
    /** @var StaffNotifier */
    private $notifier;

    public function __construct(StaffRepository $staffRepository, StaffNotifier $notifier)
    {
        $this->staffRepository = $staffRepository;
        $this->notifier        = $notifier;
    }

    /**
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
            // Refresh the staff, so that the $user->getStaff() doesn't return null
            $this->staffRepository->refresh($staff);
            $this->notifier->notifyUserInitialisation($staff);
        }
    }
}
