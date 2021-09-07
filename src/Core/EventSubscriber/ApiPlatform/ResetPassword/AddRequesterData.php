<?php

declare(strict_types=1);

namespace KLS\Core\EventSubscriber\ApiPlatform\ResetPassword;

use ApiPlatform\Core\EventListener\EventPriorities;
use Exception;
use KLS\Core\Entity\Request\ResetPassword;
use KLS\Core\Service\UserActivity\IpGeoLocManager;
use KLS\Core\Service\UserActivity\UserAgentManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AddRequesterData implements EventSubscriberInterface
{
    /** @var IpGeoLocManager */
    private $geoLocator;
    /** @var UserAgentManager */
    private $userAgent;

    public function __construct(
        IpGeoLocManager $geoLocator,
        UserAgentManager $userAgentManager
    ) {
        $this->geoLocator = $geoLocator;
        $this->userAgent  = $userAgentManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['addRequesterData', EventPriorities::PRE_WRITE],
        ];
    }

    /**
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
        $geoLocation = $geoLocation ? \implode(' ', [$geoLocation->city->name, $geoLocation->country->name]) : null;

        $userAgent = $this->userAgent->parse($request->headers->get('User-Agent'));
        $browser   = $userAgent->browser ?? null;
        $browser   = null !== $browser ? $browser->getName() . ' ' . $browser->getVersion() : '';

        $requesterData = \array_filter([
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
