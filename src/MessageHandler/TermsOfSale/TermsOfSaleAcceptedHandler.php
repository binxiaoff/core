<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\TermsOfSale;

use Swift_RfcComplianceException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Message\TermsOfSale\TermsOfSaleAccepted;
use Unilend\Repository\AcceptationLegalDocsRepository;
use Unilend\Service\TermsOfSale\TermsOfSaleNotificationSender;

class TermsOfSaleAcceptedHandler implements MessageHandlerInterface
{
    /** @var AcceptationLegalDocsRepository */
    private $acceptationLegalDocsRepository;
    /** @var TermsOfSaleNotificationSender */
    private $notificationSender;

    /**
     * @param AcceptationLegalDocsRepository $acceptationLegalDocsRepository
     * @param TermsOfSaleNotificationSender  $notificationSender
     */
    public function __construct(AcceptationLegalDocsRepository $acceptationLegalDocsRepository, TermsOfSaleNotificationSender $notificationSender)
    {
        $this->acceptationLegalDocsRepository = $acceptationLegalDocsRepository;
        $this->notificationSender             = $notificationSender;
    }

    /**
     * @param TermsOfSaleAccepted $termsOfSaleAccepted
     *
     * @throws Swift_RfcComplianceException
     */
    public function __invoke(TermsOfSaleAccepted $termsOfSaleAccepted)
    {
        $termsOfSaleAcceptation = $this->acceptationLegalDocsRepository->find($termsOfSaleAccepted->getAcceptationId());

        $this->notificationSender->sendAcceptedEmail($termsOfSaleAcceptation);
    }
}
