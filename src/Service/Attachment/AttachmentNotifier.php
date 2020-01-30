<?php

declare(strict_types=1);

namespace Unilend\Service\Attachment;

use Swift_Mailer;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\Attachment;
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
     * @param Attachment $attachment
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int
     */
    public function notifyUploaded(Attachment $attachment): int
    {
        $project = $attachment->getProject();
        $sent    = 0;

        foreach ($project->getProjectParticipations() as $participation) {
            foreach ($participation->getProjectParticipationContacts() as $contact) {
                $message = $this->messageProvider->newMessage('attachment-uploaded', [
                    'client' => [
                        'firstName' => $contact->getClient()->getFirstName(),
                    ],
                    'project' => [
                        'arranger' => $project->getArranger()->getCompany()->getName(),
                        'title'    => $project->getTitle(),
                        'hash'     => $project->getHash(),
                    ],
                ])->setTo($contact->getClient()->getEmail());
                $this->mailer->send($message);
                ++$sent;
            }
        }

        return $sent;
    }
}
