<?php

declare(strict_types=1);

namespace Unilend\Service\Staff;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Swift_Mailer;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{MarketSegment, Staff};
use Unilend\Service\TemporaryTokenGenerator;
use Unilend\SwiftMailer\TemplateMessageProvider;

class StaffNotifier
{
    /** @var TemplateMessageProvider */
    private $templateMessageProvider;
    /** @var Swift_Mailer */
    private $mailer;
    /** @var TranslatorInterface */
    private $translator;
    /** @var TemporaryTokenGenerator */
    private $temporaryTokenGenerator;

    /**
     * @param TemplateMessageProvider $templateMessageProvider
     * @param Swift_Mailer            $mailer
     * @param TranslatorInterface     $translator
     * @param TemporaryTokenGenerator $temporaryTokenGenerator
     */
    public function __construct(
        TemplateMessageProvider $templateMessageProvider,
        Swift_Mailer $mailer,
        TranslatorInterface $translator,
        TemporaryTokenGenerator $temporaryTokenGenerator
    ) {
        $this->templateMessageProvider = $templateMessageProvider;
        $this->mailer                  = $mailer;
        $this->translator              = $translator;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
    }

    /**
     * @param Staff $staff
     *
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int
     */
    public function notifyClientInitialisation(Staff $staff): int
    {
        $client = $staff->getClient();
        if (!$staff->isActive() || false === $client->isInitializationNeeded() || false === $client->isGrantedLogin()) {
            return 0;
        }

        $message = $this->templateMessageProvider->newMessage('staff-client-initialisation', [
            'client' => [
                'hash'      => $client->getPublicId(),
                'firstName' => $client->getFirstName(),
            ],
            'temporaryToken' => [
                'token' => $this->temporaryTokenGenerator->generateUltraLongToken($client)->getToken(),
            ],
            'staff' => [
                'roles' => array_map(
                    function (string $role) {
                        return $this->translator->trans('staff-roles.' . mb_strtolower($role));
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

        return $this->mailer->send($message);
    }
}
