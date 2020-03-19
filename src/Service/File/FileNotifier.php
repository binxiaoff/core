<?php

declare(strict_types=1);

namespace Unilend\Service\File;

use Swift_Mailer;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{FileVersion, ProjectStatus};
use Unilend\SwiftMailer\TemplateMessageProvider;

class FileNotifier
{
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var Swift_Mailer */
    private $mailer;

    /**
     * @param TemplateMessageProvider $messageProvider
     * @param Swift_Mailer            $mailer
     */
    public function __construct(
        TemplateMessageProvider $messageProvider,
        Swift_Mailer $mailer
    ) {
        $this->messageProvider = $messageProvider;
        $this->mailer          = $mailer;
    }

    /**
     * @param FileVersion $fileVersion
     *
     * @throws SyntaxError
     * @throws LoaderError
     * @throws RuntimeError
     *
     * @return int
     */
    public function notifyUploaded(FileVersion $fileVersion): int
    {
        $sent = 0;
        //@todo change that
        $project = $fileVersion->getProject();

        if (ProjectStatus::STATUS_PUBLISHED > $project->getCurrentStatus()->getStatus()) {
            return $sent;
        }

        foreach ($project->getProjectParticipations() as $participation) {
            if ($participation->getCompany() !== $project->getSubmitterCompany()) {
                foreach ($participation->getProjectParticipationContacts() as $contact) {
                    $message = $this->messageProvider->newMessage('attachment-uploaded', [
                        'client' => [
                            'firstName' => $contact->getClient()->getFirstName(),
                        ],
                        'project' => [
                            'submitterCompany' => $project->getSubmitterCompany()->getName(),
                            'title'            => $project->getTitle(),
                            'hash'             => $project->getHash(),
                        ],
                    ])->setTo($contact->getClient()->getEmail());
                    $sent += $this->mailer->send($message);
                }
            }
        }

        return $sent;
    }
}
