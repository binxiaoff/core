<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\ServiceTerms;

use Swift_RfcComplianceException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Message\ServiceTerms\ServiceTermsAccepted;
use Unilend\Repository\AcceptationLegalDocsRepository;
use Unilend\Service\ServiceTerms\ServiceTermsNotificationSender;

class ServiceTermsAcceptedHandler implements MessageHandlerInterface
{
    /** @var AcceptationLegalDocsRepository */
    private $acceptationLegalDocsRepository;
    /** @var ServiceTermsNotificationSender */
    private $notificationSender;

    /**
     * @param AcceptationLegalDocsRepository $acceptationLegalDocsRepository
     * @param ServiceTermsNotificationSender $notificationSender
     */
    public function __construct(AcceptationLegalDocsRepository $acceptationLegalDocsRepository, ServiceTermsNotificationSender $notificationSender)
    {
        $this->acceptationLegalDocsRepository = $acceptationLegalDocsRepository;
        $this->notificationSender             = $notificationSender;
    }

    /**
     * @param ServiceTermsAccepted $serviceTermsAccepted
     *
     * @throws Swift_RfcComplianceException
     */
    public function __invoke(ServiceTermsAccepted $serviceTermsAccepted)
    {
        $serviceTermsAcceptation = $this->acceptationLegalDocsRepository->find($serviceTermsAccepted->getAcceptationId());

        if ($serviceTermsAcceptation) {
            $this->notificationSender->sendAcceptedEmail($serviceTermsAcceptation);
        }
    }
}
