<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\ApiPlatform\Staff;

use ApiPlatform\Core\EventListener\EventPriorities;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\Staff;
use Unilend\Entity\StaffStatus;
use Unilend\Repository\StaffRepository;

class ArchivedStaffSubscriber implements EventSubscriberInterface
{
    /** @var StaffRepository */
    private $staffRepository;

    /** @var Security */
    private $security;

    /**
     * @param StaffRepository $staffRepository
     * @param Security        $security
     */
    public function __construct(StaffRepository $staffRepository, Security $security)
    {
        $this->staffRepository = $staffRepository;
        $this->security        = $security;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::VIEW => ['fetchArchivedEntity', EventPriorities::PRE_VALIDATE + 1]];
    }

    /**
     * @param ViewEvent $event
     *
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function fetchArchivedEntity(ViewEvent $event)
    {
        $previousResult = $event->getControllerResult();
        $method         = $event->getRequest()->getMethod();

        $user         = $this->security->getUser();
        $currentStaff = $user instanceof Clients ? $user->getCurrentStaff() : null;

        if (null === $currentStaff) {
            return;
        }

        if ($previousResult instanceof Staff && Request::METHOD_POST === $method) {
            $existingStaff = $this->staffRepository->findOneByClientEmailAndCompany($previousResult->getClient()->getEmail(), $previousResult->getCompany());

            if ($existingStaff && $existingStaff->isArchived()) {
                $existingStaff->setCurrentStatus(new StaffStatus($existingStaff, StaffStatus::STATUS_ACTIVE, $currentStaff));
                $existingStaff->setRoles($previousResult->getRoles());
                $existingStaff->setMarketSegments($previousResult->getMarketSegments());
                $event->setControllerResult($existingStaff);
            }
        }
    }
}
