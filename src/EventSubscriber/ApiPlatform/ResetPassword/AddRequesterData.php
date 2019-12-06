<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\ApiPlatform\ResetPassword;

use ApiPlatform\Core\EventListener\EventPriorities;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Unilend\Entity\Request\ResetPassword;
use Unilend\Service\UserActivity\IpGeoLocManager;
use Unilend\Service\UserActivity\UserAgentManager;

class AddRequesterData implements EventSubscriberInterface
{
    /** @var IpGeoLocManager */
    private $geoLocator;
    /** @var UserAgentManager */
    private $userAgent;

    /**
     * @param IpGeoLocManager  $geoLocator
     * @param UserAgentManager $userAgentManager
     */
    public function __construct(
        IpGeoLocManager $geoLocator,
        UserAgentManager $userAgentManager
    ) {
        $this->geoLocator = $geoLocator;
        $this->userAgent  = $userAgentManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['addRequesterData', EventPriorities::PRE_WRITE],
        ];
    }

    /**
     * @param ViewEvent $event
     *
     * @throws Exception
     */
    public function addRequesterData(ViewEvent $event)
    {
        $resetPasswordRequest = $event->getControllerResult();

        if (false === $resetPasswordRequest instanceof ResetPassword) {
            return;
        }

        $request = $event->getRequest();

        $ip = $request->getClientIp();

        $geoLocation = $ip ? $this->geoLocator->getGeoIpRecord($ip) : null;
        $geoLocation = $geoLocation ? implode(' ', [$geoLocation->city->name, $geoLocation->country->name]) : null;

        $userAgent = $this->userAgent->parse($request->headers->get('User-Agent'));
        $browser   = $userAgent ? $userAgent->getBrowser() : null;
        $browser   = null !== $browser ? $browser->getName() . ' ' . $browser->getVersion()->getComplete() : null;

        $requesterData = array_filter([
            'ip'       => $ip,
            'browser'  => $browser,
            'location' => $geoLocation,
            'date'     => (new \DateTimeImmutable())->format('Y-m-d'),
        ]);

        foreach ($requesterData as $property => $datum) {
            $resetPasswordRequest->{$property} = $datum;
        }

        $event->setControllerResult($resetPasswordRequest);
    }
}
