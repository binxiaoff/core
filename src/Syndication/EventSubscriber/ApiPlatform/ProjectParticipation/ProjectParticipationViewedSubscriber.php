<?php

declare(strict_types=1);

namespace Unilend\Syndication\EventSubscriber\ApiPlatform\ProjectParticipation;

use ApiPlatform\Core\EventListener\EventPriorities;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\{Event\RequestEvent, KernelEvents};
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Clients;
use Unilend\Repository\ProjectParticipationRepository;
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;
use Unilend\Syndication\Entity\{ProjectParticipation};

class ProjectParticipationViewedSubscriber implements EventSubscriberInterface
{
    /** @var Security */
    private $security;
    /** @var LoggerInterface */
    private $logger;
    /** @var ProjectParticipationManager */
    private $projectParticipationManager;
    /** @var ProjectParticipationRepository */
    private $projectParticipationRepository;

    /**
     * @param Security                       $security
     * @param ProjectParticipationManager    $projectParticipationManager
     * @param ProjectParticipationRepository $projectParticipationRepository
     * @param LoggerInterface                $logger
     */
    public function __construct(
        Security $security,
        ProjectParticipationManager $projectParticipationManager,
        ProjectParticipationRepository $projectParticipationRepository,
        LoggerInterface $logger
    ) {
        $this->security                       = $security;
        $this->projectParticipationManager    = $projectParticipationManager;
        $this->logger                         = $logger;
        $this->projectParticipationRepository = $projectParticipationRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['markAsViewedByParticipant', EventPriorities::POST_READ]];
    }

    /**
     * @param RequestEvent $event
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function markAsViewedByParticipant(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (Request::METHOD_GET !== $request->getMethod()) {
            return;
        }

        $projectParticipation = $request->attributes->get('data');
        if (!$projectParticipation instanceof ProjectParticipation) {
            return;
        }

        $user  = $this->security->getUser();
        $staff = $user instanceof Clients ? $user->getCurrentStaff() : null;

        if (!$staff) {
            $this->logger->warning('Cannot get the current staff for client', [
                'id_client' => $user->getId(),
                'class'     => self::class,
            ]);

            return;
        }

        if ($this->projectParticipationManager->isMember($projectParticipation, $staff)) {
            $projectParticipation->setParticipantLastConsulted(new \DateTimeImmutable());
            $this->projectParticipationRepository->save($projectParticipation);
        }
    }
}
