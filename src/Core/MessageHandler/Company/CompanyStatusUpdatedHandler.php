<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler\Company;

use Http\Client\Exception;
use KLS\Core\Entity\CompanyStatus;
use KLS\Core\Message\Company\CompanyStatusUpdated;
use KLS\Core\Repository\CompanyRepository;
use KLS\Core\Service\Notifier\CompanyStatus\CompanyHasSignedNotifier;
use Nexy\Slack\Exception\SlackApiException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CompanyStatusUpdatedHandler implements MessageHandlerInterface
{
    private CompanyRepository $companyRepository;
    private CompanyHasSignedNotifier $companySignedNotifier;

    public function __construct(CompanyRepository $companyRepository, CompanyHasSignedNotifier $companySignedNotifier)
    {
        $this->companyRepository     = $companyRepository;
        $this->companySignedNotifier = $companySignedNotifier;
    }

    /**
     * @throws Exception
     * @throws SlackApiException
     */
    public function __invoke(CompanyStatusUpdated $companyStatusUpdated)
    {
        $company = $this->companyRepository->find($companyStatusUpdated->getCompanyId());

        if (
            $company && CompanyStatus::STATUS_PROSPECT === $companyStatusUpdated->getPreviousStatus()
            && CompanyStatus::STATUS_SIGNED === $companyStatusUpdated->getNextStatus()
        ) {
            $this->companySignedNotifier->notify($company);
        }
    }
}
