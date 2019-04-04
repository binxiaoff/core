<?php

namespace Unilend\Bundle\FrontBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Unilend\Service\TermsOfSaleManager;

class TermsOfSaleSubscriber implements EventSubscriberInterface
{
    /** @var TermsOfSaleManager */
    private $termsOfSaleManager;

    /**
     * @param TermsOfSaleManager $termsOfSaleManager
     */
    public function __construct(TermsOfSaleManager $termsOfSaleManager)
    {
        $this->termsOfSaleManager = $termsOfSaleManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'checkLegalDoc'
        ];
    }

    public function checkLegalDoc(): void
    {
        $this->termsOfSaleManager->checkCurrentVersionAccepted();
    }
}
