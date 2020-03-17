<?php

declare(strict_types=1);

namespace Unilend\Service\Attachment;

use Swift_Mailer;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{FileVersion, ProjectStatus};
use Unilend\SwiftMailer\TemplateMessageProvider;

class AttachmentNotifier
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
     * @param FileVersion $attachment
     *
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     *
     * @return int
     */
    public function notifyUploaded(FileVersion $attachment): int
    {
        $sent = 0;
        //@todo change that
        $project = $attachment->getProject();

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
