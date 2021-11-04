<?php

declare(strict_types=1);

namespace KLS\Core\Service\User\SlackNotifier;

use KLS\Core\Entity\User;
use Nexy\Slack\Attachment;
use Nexy\Slack\AttachmentField;
use Nexy\Slack\Client as Slack;
use Nexy\Slack\MessageInterface;

class UserCreatedNotifier
{
    private Slack $slack;

    public function __construct(Slack $client)
    {
        $this->slack = $client;
    }

    /**
     * @throws \Http\Client\Exception
     * @throws \Nexy\Slack\Exception\SlackApiException
     */
    public function notify(User $user): void
    {
        $this->slack->sendMessage($this->createSlackMessage($user));
    }

    public function createSlackMessage(User $user): MessageInterface
    {
        $userAdmin = [];
        foreach ($user->getStaff() as $staff) {
            if ($staff->isAdmin()) {
                $userAdmin[] = $staff->getCompany()->getDisplayName();
            }
        }

        $userInfo = [
            'Prénom : ' . $user->getFirstName(),
            'Nom : ' . $user->getLastName(),
            'Email : ' . $user->getEmail(),
            'Job : ' . $user->getJobFunction(),
        ];

        return $this->slack->createMessage()
            ->enableMarkdown()
            ->setText($user->getFirstName() . ' ' . $user->getLastName() . ' vient d\'initialiser son compte')
            ->attach(
                (new Attachment())
                    ->addField(new AttachmentField(
                        'Administrateur sur',
                        false === empty($userAdmin) ? '• ' . \implode(PHP_EOL . '• ', $userAdmin) : '',
                        true
                    ))
                    ->addField(new AttachmentField(
                        'Informations',
                        '• ' . \implode(PHP_EOL . '• ', $userInfo),
                        true
                    ))
            )
        ;
    }
}
