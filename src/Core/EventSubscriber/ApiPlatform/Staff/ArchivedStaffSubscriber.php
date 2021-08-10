<?php

declare(strict_types=1);

namespace KLS\Core\EventSubscriber\ApiPlatform\Staff;

use ApiPlatform\Core\EventListener\EventPriorities;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\StaffStatus;
use KLS\Core\Entity\User;
use KLS\Core\Repository\StaffRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class ArchivedStaffSubscriber implements EventSubscriberInterface
{
    /** @var StaffRepository */
    private $staffRepository;

    /** @var Security */
    private $security;

    public function __construct(StaffRepository $staffRepository, Security $security)
    {
        $this->staffRepository = $staffRepository;
        $this->security        = $security;
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::VIEW => ['fetchArchivedEntity', EventPriorities::PRE_VALIDATE]];
    }

    /**
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function fetchArchivedEntity(ViewEvent $event)
    {
        $previousResult = $event->getControllerResult();
        $method         = $event->getRequest()->getMethod();

        $user         = $this->security->getUser();
        $currentStaff = $user instanceof User ? $user->getCurrentStaff() : null;

        if (null === $currentStaff) {
            return;
        }

        if ($previousResult instanceof Staff && Request::METHOD_POST === $method) {
            $existingStaff = $this->staffRepository->findOneByEmailAndCompany($previousResult->getUser()->getEmail(), $previousResult->getCompany());

            if ($existingStaff && $existingStaff->isArchived()) {
                $existingStaff->setCurrentStatus(new StaffStatus($existingStaff, StaffStatus::STATUS_ACTIVE, $currentStaff));
                $event->setControllerResult($existingStaff);
            }
        }
    }
}
