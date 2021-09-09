<?php

declare(strict_types=1);

namespace KLS\Core\Service\Notifier\CompanyStatus;

use Http\Client\Exception;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Staff;
use Nexy\Slack\Attachment;
use Nexy\Slack\AttachmentField;
use Nexy\Slack\Client as Slack;
use Nexy\Slack\Exception\SlackApiException;
use Nexy\Slack\MessageInterface;

class CompanyHasSignedNotifier
{
    private Slack $slack;

    public function __construct(Slack $client)
    {
        $this->slack = $client;
    }

    /**
     * @throws Exception
     * @throws SlackApiException
     */
    public function notify(Company $company): void
    {
        $this->slack->sendMessage($this->createSlackMessage($company));
    }

    public function createSlackMessage(Company $company): MessageInterface
    {
        $adminsEmail   = [];
        $managersEmail = [];

        /** @var Staff $staff */
        foreach ($company->getStaff() as $staff) {
            if ($staff->isManager()) {
                $managersEmail[] = $staff->getUser()->getEmail();
            }
            if ($staff->isAdmin()) {
                $adminsEmail[] = $staff->getUser()->getEmail();
            }
        }

        return $this->slack->createMessage()->enableMarkdown()
            ->setText('L\'entité ' . $company->getDisplayName() . 'est maintenant cliente de la plateforme')
            ->attach(
                (new Attachment())
                    ->addField(new AttachmentField(
                        'Administrateurs',
                        false === empty($adminsEmail) ? '• ' . \implode(PHP_EOL . '• ', $adminsEmail) : '',
                        true
                    ))
                    ->addField(new AttachmentField(
                        'Managers',
                        false === empty($managersEmail) ? '• ' . \implode(PHP_EOL . '• ', $managersEmail) : '',
                        true
                    ))
            )
        ;
    }
}
