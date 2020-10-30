<?php

declare(strict_types=1);

namespace Unilend\Service\Staff;

use Doctrine\ORM\ORMException;
use JsonException;
use Swift_Mailer;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{MarketSegment, Staff};
use Unilend\Service\TemporaryTokenGenerator;
use Unilend\SwiftMailer\MailjetMessage;

class StaffNotifier
{
    /** @var Swift_Mailer */
    private Swift_Mailer $mailer;
    /** @var TranslatorInterface */
    private TranslatorInterface $translator;
    /** @var TemporaryTokenGenerator */
    private TemporaryTokenGenerator $temporaryTokenGenerator;

    /**
     * @param Swift_Mailer            $mailer
     * @param TranslatorInterface     $translator
     * @param TemporaryTokenGenerator $temporaryTokenGenerator
     */
    public function __construct(
        Swift_Mailer $mailer,
        TranslatorInterface $translator,
        TemporaryTokenGenerator $temporaryTokenGenerator
    ) {
        $this->mailer                  = $mailer;
        $this->translator              = $translator;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
    }

    /**
     * @param Staff $staff
     *
     * @return int
     *
     * @throws JsonException
     * @throws ORMException
     */
    public function notifyClientInitialisation(Staff $staff): int
    {
        $client = $staff->getClient();
        if (!$staff->isActive() || false === $client->isInitializationNeeded() || false === $client->isGrantedLogin() || false === $staff->getCompany()->hasSigned()) {
            return 0;
        }

        $message = (new MailjetMessage())
            ->setTo($client->getEmail())
            ->setTemplateId(1)
            ->setVars([
                'client' => [
                    'publicId'  => $client->getPublicId(),
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
                    'company' => ['displayName' => $staff->getCompany()->getDisplayName()],
                ],
            ])
        ;


        return $this->mailer->send($message);
    }
}
