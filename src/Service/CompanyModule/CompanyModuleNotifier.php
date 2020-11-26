<?php

declare(strict_types=1);

namespace Unilend\Service\CompanyModule;

use Http\Client\Exception;
use Nexy\Slack\{ Attachment, AttachmentField, Client as Slack, Exception\SlackApiException, MessageInterface};
use Unilend\Core\Entity\Clients;
use Unilend\Core\Entity\CompanyModule;
use Unilend\Core\Entity\{CompanyModuleLog};

class CompanyModuleNotifier
{
    /** @var Slack */
    private Slack $slack;

    /**
     * @param Slack $slack
     */
    public function __construct(Slack $slack)
    {
        $this->slack = $slack;
    }

    /**
     * @param CompanyModule $companyModule
     *
     * @throws Exception
     * @throws SlackApiException
     */
    public function notifyModuleActivation(CompanyModule $companyModule)
    {
        /** @var CompanyModuleLog $lastLog */
        $lastLog = $companyModule->getLogs()->last();

        if ($lastLog) {
            $this->slack->sendMessage($this->createSlackMessage($lastLog));
        }
    }

    /**
     * @param CompanyModuleLog $log
     *
     * @return MessageInterface
     */
    private function createSlackMessage(CompanyModuleLog $log): MessageInterface
    {
        return $this->slack->createMessage()
            ->enableMarkdown()
            ->setText("Un module vient d'être " . ($log->isActivated() ? 'activé' : 'désactivé'))
            ->attach(
                (new Attachment())
                    ->addField(new AttachmentField('Entité', $log->getCompanyModule()->getCompany()->getDisplayName(), true))
                    ->addField(new AttachmentField('Module', $this->getModuleHumanLabel($log->getCompanyModule()), true))
                    ->addField(new AttachmentField('Utilisateur', $this->getClientDisplayIdentifier($log->getAddedBy()->getClient()), true))
            );
    }

    /**
     * @param CompanyModule $companyModule
     *
     * @return string
     */
    private function getModuleHumanLabel(CompanyModule $companyModule): string
    {
        switch ($companyModule->getCode()) {
            case CompanyModule::MODULE_ARRANGEMENT:
                return 'Arrangement';
            case CompanyModule::MODULE_AGENCY:
                return 'Agency';
            case CompanyModule::MODULE_PARTICIPATION:
                return 'Participation';
            case CompanyModule::MODULE_ARRANGEMENT_EXTERNAL_BANK:
                return 'Arrangement Toute Banques';
            default:
                throw new \LogicException('This code should not be reached');
        }
    }

    /**
     * @param Clients $client
     *
     * @return string
     */
    private function getClientDisplayIdentifier(Clients $client)
    {
        if ($client->getFirstName() && $client->getLastName()) {
            return $client->getFirstName() . ' ' . $client->getLastName();
        }

        return $client->getEmail();
    }
}
