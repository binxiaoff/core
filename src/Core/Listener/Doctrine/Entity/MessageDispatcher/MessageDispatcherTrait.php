<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher;

use Symfony\Component\Messenger\MessageBusInterface;

trait MessageDispatcherTrait
{
    /** @var MessageBusInterface */
    private $messageBus;

    /**
     * @param MessageBusInterface $messageBus
     */
    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }
}
