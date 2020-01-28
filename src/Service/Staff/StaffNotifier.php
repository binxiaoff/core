<?php

declare(strict_types=1);

namespace Unilend\Service\Staff;

use Swift_Mailer;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{MarketSegment, Staff};
use Unilend\SwiftMailer\TemplateMessageProvider;

class StaffNotifier
{
    /** @var TemplateMessageProvider */
    private $templateMessageProvider;
    /** @var Swift_Mailer */
    private $mailer;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TemplateMessageProvider $templateMessageProvider
     * @param Swift_Mailer            $mailer
     * @param TranslatorInterface     $translator
     */
    public function __construct(TemplateMessageProvider $templateMessageProvider, Swift_Mailer $mailer, TranslatorInterface $translator)
    {
        $this->templateMessageProvider = $templateMessageProvider;
        $this->mailer                  = $mailer;
        $this->translator              = $translator;
    }

    /**
     * @param Staff $staff
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendClientInitialisation(Staff $staff)
    {
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
                    'roles' => array_map(
                        function (string $role) {
                            return $this->translator->trans('staff-roles.' . $role);
                        },
                        $staff->getRoles()
                    ),
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
