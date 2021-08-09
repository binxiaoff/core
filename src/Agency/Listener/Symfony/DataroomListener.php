<?php

declare(strict_types=1);

namespace KLS\Agency\Listener\Symfony;

use KLS\Agency\Entity\Agent;
use KLS\Agency\Entity\Participation;
use KLS\Agency\Entity\ParticipationPool;
use KLS\Agency\Entity\Project;
use KLS\Core\Controller\Dataroom\Delete;
use KLS\Core\Controller\Dataroom\Get;
use KLS\Core\Controller\Dataroom\Post;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Inject the correct drive based called url.
 */
class DataroomListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => ['onControllerArguments', -1],
        ];
    }

    public function onControllerArguments(ControllerArgumentsEvent $event)
    {
        $controller = $event->getController();

        if (false === \is_object($controller) || false === \in_array(\get_class($controller), [Get::class, Post::class, Delete::class])) {
            return;
        }

        $arguments = $event->getArguments();
        $request   = $event->getRequest();
        $index     = null;

        for ($argument = \reset($arguments); null === $index && $argument = \current($arguments); \next($arguments)) {
            if (\in_array(\get_class($argument), [Project::class, Participation::class, ParticipationPool::class, Agent::class])) {
                $index = \key($arguments);
            }
        }

        if (null === $argument || null === $index) {
            return;
        }

        switch (\get_class($argument)) {
            // For now the project houses the borrower drives
            case Project::class:
                $drive = $request->attributes->get('drive');

                if ('shared' === $drive) {
                    $arguments[$index] = $argument->getBorrowerSharedDrive();
                }

                if ('confidential' === $drive) {
                    $arguments[$index] = $argument->getBorrowerConfidentialDrive();
                }

                break;

            case Participation::class:
                $arguments[$index] = $argument->getConfidentialDrive();

                break;

            case ParticipationPool::class:
                $arguments[$index] = $argument->getSharedDrive();

                break;

            case Agent::class:
                $arguments[$index] = $argument->getConfidentialDrive();

                break;
        }

        $event->setArguments($arguments);
    }
}
