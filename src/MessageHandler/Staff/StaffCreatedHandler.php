<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Staff;

use Swift_Mailer;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Entity\MarketSegment;
use Unilend\Message\Staff\StaffCreated;
use Unilend\Service\Mailer\MailQueueManager;
use Unilend\Service\MailerManager;
use Unilend\SwiftMailer\TemplateMessageProvider;

class StaffCreatedHandler implements MessageHandlerInterface
{
    /** @var MailerManager */
    private $mailerManager;
    /** @var Environment */
    private $twig;
    /** @var TranslatorInterface */
    private $translator;
    /** @var MailQueueManager */
    private $mailQueue;
    /** @var Swift_Mailer */
    private $mailer;
    /** @var TemplateMessageProvider */
    private $templateMessageProvider;

    /**
     * @param TranslatorInterface     $translator
     * @param TemplateMessageProvider $templateMessageProvider
     * @param Swift_Mailer            $mailer
     */
    public function __construct(
        TranslatorInterface $translator,
        TemplateMessageProvider $templateMessageProvider,
        Swift_Mailer $mailer
    ) {
        $this->translator              = $translator;
        $this->mailer                  = $mailer;
        $this->templateMessageProvider = $templateMessageProvider;
    }

    /**
     * @param StaffCreated $staffCreated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(StaffCreated $staffCreated)
    {
        $staff = $staffCreated->getStaff();

        $client = $staff->getClient();

        $token = $client->getLastTemporaryToken();

        if ($token) {
            $message = $this->templateMessageProvider->newMessage('staff-client-initialisation', [
                'client' => [
                    'hash'      => $client->getHash(),
                    'firstName' => $client->getFirstName(),
                ],
                'temporaryToken' => [
                    'token' => $token->getToken(),
                ],
                'staff' => [
                    'role'           => $this->translator->trans('staff-roles.' . $staff->getRoles()[0]),
                    'marketSegments' => $staff->getMarketSegments()->map(function (MarketSegment $marketSegment) {
                        return $this->translator->trans('market-segment.' . $marketSegment->getLabel());
                    })->toArray(),
                ],
            ])
                ->setTo($client->getEmail())
            ;

            $this->mailer->send($message);
        }
    }
}
