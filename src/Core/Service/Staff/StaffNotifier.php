<?php

declare(strict_types=1);

namespace Unilend\Core\Service\Staff;

use Doctrine\ORM\ORMException;
use JsonException;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Core\Entity\{Staff};
use Unilend\Core\Service\TemporaryTokenGenerator;
use Unilend\Core\SwiftMailer\MailjetMessage;

class StaffNotifier
{
    /** @var Swift_Mailer */
    private Swift_Mailer $mailer;
    /** @var TemporaryTokenGenerator */
    private TemporaryTokenGenerator $temporaryTokenGenerator;
    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @param Swift_Mailer            $mailer
     * @param TemporaryTokenGenerator $temporaryTokenGenerator
     * @param RouterInterface         $router
     */
    public function __construct(
        Swift_Mailer $mailer,
        TemporaryTokenGenerator $temporaryTokenGenerator,
        RouterInterface $router
    ) {
        $this->mailer                  = $mailer;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
        $this->router = $router;
    }

    /**
     * @param Staff $staff
     *
     * @return int
     *
     * @throws JsonException
     * @throws ORMException
     */
    public function notifyUserInitialisation(Staff $staff): int
    {
        $user = $staff->getUser();
        if (!$staff->isActive() || false === $user->isInitializationNeeded() || false === $user->isGrantedLogin() || false === $staff->getCompany()->hasSigned()) {
            return 0;
        }

        $token = $this->temporaryTokenGenerator->generateUltraLongToken($user)->getToken();

        $message = (new MailjetMessage())
            ->setTo($user->getEmail())
            ->setTemplateId(MailjetMessage::TEMPLATE_STAFF_USER_INITIALISATION)
            ->setVars([
                    'inscriptionFinalisationUrl' =>
                        $this->router->generate(
                            'front_initialAccount',
                            ['temporaryTokenPublicId' => $token, 'userPublicId' => $user->getPublicId()],
                            RouterInterface::ABSOLUTE_URL
                        ),
                    'marketSegments' => ' ',
                    'roles' => $staff->isManager() ? 'manager' : '',
                    'company_displayName' => $staff->getCompany()->getDisplayName(),
                    'client_firstName' =>  $user->getFirstName(),
            ])
        ;


        return $this->mailer->send($message);
    }
}
