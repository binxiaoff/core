<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Symfony;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Unilend\Core\Controller\Dataroom\Delete;
use Unilend\Core\Controller\Dataroom\Get;
use Unilend\Core\Controller\Dataroom\Post;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\Interfaces\DriveAwareInterface;

/**
 * Inject the correct drive based called url.
 */
class DataroomListener implements EventSubscriberInterface
{
    private array                     $dataroomCarriers;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(array $dataroomCarriers, PropertyAccessorInterface $propertyAccessor)
    {
        $this->dataroomCarriers = $dataroomCarriers;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => ['onControllerArguments', -1],
        ];
    }

    public function onControllerArguments(ControllerArgumentsEvent $event): void
    {
        $controller = $event->getController();

        if (false === \is_object($controller) || false === \in_array(\get_class($controller), [Get::class, Post::class, Delete::class])) {
            return;
        }

        $arguments = $event->getArguments();
        $index     = null;

        for ($argument = reset($arguments); null === $index && $argument = current($arguments); next($arguments)) {
            if (\in_array(\get_class($argument), $this->dataroomCarriers, true)) {
                $index = key($arguments);
            }
        }

        if (null === $argument || null === $index) {
            return;
        }
        $drivePropertyPath = $event->getRequest()->attributes->get('drive');
        if ($drivePropertyPath) {
            $drive = $this->propertyAccessor->getValue($argument, $drivePropertyPath);
            if (false === ($drive instanceof Drive)) {
                throw new \RuntimeException(sprintf('Cannot find the drive from %s::%s', get_class($argument), $drivePropertyPath));
            }
            $arguments[$index] = $drive;
        } elseif ($argument instanceof DriveAwareInterface) {
            $arguments[$index] = $argument->getDrive();
        } else {
            throw new \RuntimeException(sprintf('Cannot find the drive for the class %s', get_class($argument)));
        }

        $event->setArguments($arguments);
    }
}
