<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Staff;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\Staff\StaffCreated;
use Unilend\Repository\StaffRepository;
use Unilend\Service\Staff\StaffNotifier;

class StaffCreatedHandler implements MessageHandlerInterface
{
    /** @var StaffRepository */
    private $repository;
    /** @var StaffNotifier */
    private $notifier;

    /**
     * @param StaffRepository $repository
     * @param StaffNotifier   $notifier
     */
    public function __construct(StaffRepository $repository, StaffNotifier $notifier)
    {
        $this->repository = $repository;
        $this->notifier   = $notifier;
    }

    /**
     * @param StaffCreated $staffCreated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(StaffCreated $staffCreated)
    {
        $staff = $this->repository->find($staffCreated->getStaffId());

        if ($staff) {
            $this->notifier->notifyClientInitialisation($staff);
        }
    }
}
