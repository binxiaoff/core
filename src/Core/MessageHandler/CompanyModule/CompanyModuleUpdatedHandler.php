<?php

declare(strict_types=1);

namespace Unilend\Core\MessageHandler\CompanyModule;

use Http\Client\Exception;
use Nexy\Slack\Exception\SlackApiException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Core\Message\CompanyModule\CompanyModuleUpdated;
use Unilend\Core\Repository\CompanyModuleRepository;
use Unilend\Service\CompanyModule\CompanyModuleNotifier;

class CompanyModuleUpdatedHandler implements MessageHandlerInterface
{
    /** @var CompanyModuleRepository $om */
    private CompanyModuleRepository $repository;

    /** @var CompanyModuleNotifier  */
    private CompanyModuleNotifier $notifier;

    /**
     * @param CompanyModuleRepository $repository
     * @param CompanyModuleNotifier   $notifier
     */
    public function __construct(CompanyModuleRepository $repository, CompanyModuleNotifier $notifier)
    {
        $this->repository = $repository;
        $this->notifier = $notifier;
    }

    /**
     * @param CompanyModuleUpdated $companyModuleUpdated
     *
     * @throws Exception
     * @throws SlackApiException
     */
    public function __invoke(CompanyModuleUpdated $companyModuleUpdated)
    {
        $companyModule = $this->repository->find($companyModuleUpdated->getCompanyModuleId());

        if ($companyModule) {
            $this->notifier->notifyModuleActivation($companyModule);
        }
    }
}
