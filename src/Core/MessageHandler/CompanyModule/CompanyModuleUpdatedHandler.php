<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler\CompanyModule;

use Http\Client\Exception;
use KLS\Core\Message\CompanyModule\CompanyModuleUpdated;
use KLS\Core\Repository\CompanyModuleRepository;
use KLS\Core\Service\CompanyModule\CompanyModuleNotifier;
use Nexy\Slack\Exception\SlackApiException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CompanyModuleUpdatedHandler implements MessageHandlerInterface
{
    private CompanyModuleRepository $repository;

    private CompanyModuleNotifier $notifier;

    public function __construct(CompanyModuleRepository $repository, CompanyModuleNotifier $notifier)
    {
        $this->repository = $repository;
        $this->notifier   = $notifier;
    }

    /**
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
