<?php

declare(strict_types=1);

namespace KLS\Core\Service\Staff;

use KLS\Core\Entity\Staff;
use KLS\Core\Mailer\MailjetMessage;
use KLS\Core\Service\TemporaryTokenGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StaffNotifier
{
    private RouterInterface         $router;
    private TranslatorInterface     $translator;
    private TemporaryTokenGenerator $temporaryTokenGenerator;
    private MailerInterface         $mailer;
    private LoggerInterface         $logger;

    public function __construct(
        RouterInterface $router,
        TranslatorInterface $translator,
        TemporaryTokenGenerator $temporaryTokenGenerator,
        MailerInterface $mailer,
        LoggerInterface $logger
    ) {
        $this->router                  = $router;
        $this->translator              = $translator;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
        $this->mailer                  = $mailer;
        $this->logger                  = $logger;
    }

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

        try {
            $templateId = MailjetMessage::TEMPLATE_STAFF_USER_INITIALISATION;
            $token      = $this->temporaryTokenGenerator->generateUltraLongToken($user)->getToken();

            $translatedCompanyGroupTags = \array_map(
                fn (string $tag) => $this->translator->trans('market-segment.' . $tag),
                $staff->getCompanyGroupTags()
            );
            $message = (new MailjetMessage())
                ->to($user->getEmail())
                ->setTemplateId($templateId)
                ->setVars([
                    'inscriptionFinalisationUrl' => $this->router->generate(
                        'front_initialAccount',
                        ['temporaryTokenPublicId' => $token, 'userPublicId' => $user->getPublicId()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    'marketSegments'      => \implode(', ', $translatedCompanyGroupTags),
                    'roles'               => $staff->isManager() ? 'manager' : '',
                    'company_displayName' => $staff->getCompany()->getDisplayName(),
                    'client_firstName'    => $user->getFirstName() ?? '',
                ])
            ;

            $this->mailer->send($message);
        } catch (\Throwable $throwable) {
            $this->logger->error(
                \sprintf(
                    'Email sending failed for %s with template id %d. Error: %s',
                    $user->getEmail(),
                    $templateId,
                    $throwable->getMessage()
                ),
                ['throwable' => $throwable]
            );

            return 0;
        }

        return 1;
    }
}
