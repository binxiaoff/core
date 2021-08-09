<?php

declare(strict_types=1);

namespace KLS\Core\Service\Staff;

use Doctrine\ORM\ORMException;
use JsonException;
use KLS\Core\Entity\Staff;
use KLS\Core\Service\TemporaryTokenGenerator;
use KLS\Core\SwiftMailer\MailjetMessage;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StaffNotifier
{
    private RouterInterface $router;
    private TranslatorInterface $translator;
    private TemporaryTokenGenerator $temporaryTokenGenerator;
    private Swift_Mailer $mailer;

    public function __construct(
        RouterInterface $router,
        TranslatorInterface $translator,
        TemporaryTokenGenerator $temporaryTokenGenerator,
        Swift_Mailer $mailer
    ) {
        $this->router                  = $router;
        $this->translator              = $translator;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
        $this->mailer                  = $mailer;
    }

    /**
     * @throws JsonException
     * @throws ORMException
     */
    public function notifyUserInitialisation(Staff $staff): int
    {
        $user = $staff->getUser();

        if (
            !$staff->isActive()
            || false === $user->isInitializationNeeded()
            || false === $user->isGrantedLogin()
            || false === $staff->getCompany()->hasSigned()
        ) {
            return 0;
        }

        $token = $this->temporaryTokenGenerator->generateUltraLongToken($user)->getToken();

        $translatedCompanyGroupTags = \array_map(fn (string $tag) => $this->translator->trans('market-segment.' . $tag), $staff->getCompanyGroupTags());

        $message = (new MailjetMessage())
            ->setTo($user->getEmail())
            ->setTemplateId(MailjetMessage::TEMPLATE_STAFF_USER_INITIALISATION)
            ->setVars([
                'inscriptionFinalisationUrl' => $this->router->generate(
                    'front_initialAccount',
                    ['temporaryTokenPublicId' => $token, 'userPublicId' => $user->getPublicId()],
                    RouterInterface::ABSOLUTE_URL
                ),
                'marketSegments'      => \implode(', ', $translatedCompanyGroupTags),
                'roles'               => $staff->isManager() ? 'manager' : '',
                'company_displayName' => $staff->getCompany()->getDisplayName(),
                'client_firstName'    => $user->getFirstName() ?? '',
            ])
        ;

        return $this->mailer->send($message);
    }
}
