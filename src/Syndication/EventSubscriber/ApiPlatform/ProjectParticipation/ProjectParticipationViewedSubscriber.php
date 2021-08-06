<?php

declare(strict_types=1);

namespace Unilend\Syndication\EventSubscriber\ApiPlatform\ProjectParticipation;

use ApiPlatform\Core\EventListener\EventPriorities;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\{ProjectParticipation};
use Unilend\Syndication\Repository\ProjectParticipationRepository;
use Unilend\Syndication\Service\ProjectParticipation\ProjectParticipationManager;

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

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['markAsViewedByParticipant', EventPriorities::POST_READ]];
    }

    /**
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
        $staff = $user instanceof User ? $user->getCurrentStaff() : null;

        if (!$staff) {
            $this->logger->warning('Cannot get the current staff for user', [
                'id_user' => $user->getId(),
                'class'   => self::class,
            ]);

            return;
        }

        if ($this->projectParticipationManager->isActiveMember($projectParticipation, $staff)) {
            $projectParticipation->setParticipantLastConsulted(new \DateTimeImmutable());
            $this->projectParticipationRepository->save($projectParticipation);
        }
    }
}
