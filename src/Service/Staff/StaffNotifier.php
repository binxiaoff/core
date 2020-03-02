<?php

declare(strict_types=1);

namespace Unilend\Service\Staff;

use Exception;
use LogicException;
use Swift_Mailer;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{MarketSegment, Staff, TemporaryToken};
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
     * @param Staff          $staff
     * @param TemporaryToken $temporaryToken
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function notifyClientInitialisation(Staff $staff, TemporaryToken $temporaryToken): void
    {
        $client = $staff->getClient();

        if (false === $temporaryToken->isValid()) {
            throw new LogicException('The token should be valid at this point');
        }

        if ($staff->getClient() !== $temporaryToken->getClient()) {
            throw new LogicException('The staff and the temporaryToken should refer to the same client');
        }

        $message = $this->templateMessageProvider->newMessage('staff-client-initialisation', [
            'client' => [
                'hash'      => $client->getPublicId(),
                'firstName' => $client->getFirstName(),
            ],
            'temporaryToken' => [
                'token' => $temporaryToken->getToken(),
            ],
            'staff' => [
                'roles' => array_map(
                    function (string $role) {
                        return $this->translator->trans('staff-roles.' . mb_strtoupper($role));
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
