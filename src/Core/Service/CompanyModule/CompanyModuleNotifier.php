<?php

declare(strict_types=1);

namespace KLS\Core\Service\CompanyModule;

use Http\Client\Exception;
use KLS\Core\Entity\CompanyModule;
use KLS\Core\Entity\CompanyModuleLog;
use KLS\Core\Entity\User;
use Nexy\Slack\Attachment;
use Nexy\Slack\AttachmentField;
use Nexy\Slack\Client as Slack;
use Nexy\Slack\Exception\SlackApiException;
use Nexy\Slack\MessageInterface;

class CompanyModuleNotifier
{
    private Slack $slack;

    public function __construct(Slack $slack)
    {
        $this->slack = $slack;
    }

    /**
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

    private function createSlackMessage(CompanyModuleLog $log): MessageInterface
    {
        return $this->slack->createMessage()
            ->enableMarkdown()
            ->setText("Un module vient d'être " . ($log->isActivated() ? 'activé' : 'désactivé'))
            ->attach(
                (new Attachment())
                    ->addField(new AttachmentField('Entité', $log->getCompanyModule()->getCompany()->getDisplayName(), true))
                    ->addField(new AttachmentField('Module', $this->getModuleHumanLabel($log->getCompanyModule()), true))
                    ->addField(new AttachmentField('Utilisateur', $this->getUserDisplayIdentifier($log->getAddedBy()->getUser()), true))
            )
        ;
    }

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
     * @return string
     */
    private function getUserDisplayIdentifier(User $user)
    {
        if ($user->getFirstName() && $user->getLastName()) {
            return $user->getFirstName() . ' ' . $user->getLastName();
        }

        return $user->getEmail();
    }
}
