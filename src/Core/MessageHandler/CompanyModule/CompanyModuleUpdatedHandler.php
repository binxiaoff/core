<?php

declare(strict_types=1);

namespace Unilend\Core\MessageHandler\CompanyModule;

use Http\User\Exception;
use Nexy\Slack\Exception\SlackApiException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Core\Message\CompanyModule\CompanyModuleUpdated;
use Unilend\Core\Repository\CompanyModuleRepository;
use Unilend\Core\Service\CompanyModule\CompanyModuleNotifier;

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
