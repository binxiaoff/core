<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{Bids, Clients, Projects};
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class DemoMailerManager
{
    const RECIPIENT_TYPE_ARRANGER  = 'arranger';
    const RECIPIENT_TYPE_LENDERS   = 'lenders';
    const RECIPIENT_TYPE_RUN       = 'run';
    const RECIPIENT_TYPE_SUBMITTER = 'submitter';

    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var RouterInterface */
    private $router;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     * @param EntityManagerInterface  $entityManager
     * @param RouterInterface         $router
     * @param TranslatorInterface     $translator
     */
    public function __construct(
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        TranslatorInterface $translator
    )
    {
        $this->messageProvider = $messageProvider;
        $this->mailer          = $mailer;
        $this->entityManager   = $entityManager;
        $this->router          = $router;
        $this->translator      = $translator;
    }

    /**
     * @param Projects $project
     * @return int
     * @throws \Swift_RfcComplianceException
     */
    public function sendProjectRequest(Projects $project): int
    {
        $formatter = new \NumberFormatter('fr_FR', \NumberFormatter::DEFAULT_STYLE);
        $keywords  = [
            'firstName'  => '',
            'projectUrl' => $this->router->generate('demo_project_details', ['hash' => $project->getHash()], RouterInterface::ABSOLUTE_URL),
            'borrower'   => $project->getIdCompany()->getName(),
            'amount'     => $formatter->format($project->getAmount()),
            'duration'   => $this->translator->trans('demo-project-request_simulator-duration-select-option', [
                '%count%'    => $project->getPeriod() / 12,
                '%duration%' => $project->getPeriod() / 12
            ])
        ];

        $sent       = 0;
        $recipients = $this->getProjectRecipients($project, [
            self::RECIPIENT_TYPE_ARRANGER,
            self::RECIPIENT_TYPE_RUN,
            self::RECIPIENT_TYPE_SUBMITTER
        ]);

        foreach ($recipients as $recipient) {
            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getPrenom();
                $message               = $this->messageProvider->newMessage('project-new', $keywords);
                $message->setTo($recipient->getEmail());

                $sent += $this->mailer->send($message);
            }
        }

        return $sent;
    }

    /**
     * @param Projects $project
     * @return int
     * @throws \Swift_RfcComplianceException
     */
    public function sendArrangerScoringChanged(Projects $project): int
    {
        return $this->sendProjectScoringChanged($project, 'arrangeur', $project->getNatureProject());
    }

    /**
     * @param Projects $project
     * @return int
     * @throws \Swift_RfcComplianceException
     */
    public function sendRunScoringChanged(Projects $project): int
    {
        return $this->sendProjectScoringChanged($project, 'RUN', $project->getObjectifLoan());
    }

    /**
     * @param Projects $project
     * @param string   $name
     * @param string   $value
     * @return int
     * @throws \Swift_RfcComplianceException
     */
    private function sendProjectScoringChanged(Projects $project, string $name, string $value): int
    {
        $keywords = [
            'firstName'    => '',
            'projectUrl'   => $this->router->generate('demo_project_details', ['hash' => $project->getHash()], RouterInterface::ABSOLUTE_URL),
            'projectName'  => $project->getIdCompany()->getName() . ' / ' . $project->getTitle(),
            'scoringName'  => $name,
            'scoringValue' => $value
        ];

        $sent       = 0;
        $recipients = $this->getProjectRecipients($project, [
            self::RECIPIENT_TYPE_ARRANGER,
            self::RECIPIENT_TYPE_RUN,
            self::RECIPIENT_TYPE_SUBMITTER
        ]);

        foreach ($recipients as $recipient) {
            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getPrenom();
                $message               = $this->messageProvider->newMessage('project-scoring', $keywords);
                $message->setTo($recipient->getEmail());

                $sent += $this->mailer->send($message);
            }
        }

        return $sent;
    }

    /**
     * @param Projects $project
     * @return int
     * @throws \Swift_RfcComplianceException
     */
    public function sendProjectPublication(Projects $project): int
    {
        $keywords = [
            'firstName'   => '',
            'projectUrl'  => $this->router->generate('demo_lender_project_details', ['slug' => $project->getSlug()], RouterInterface::ABSOLUTE_URL),
            'projectName' => $project->getIdCompany()->getName() . ' / ' . $project->getTitle()
        ];

        $sent       = 0;
        $recipients = $this->getProjectRecipients($project, [
            self::RECIPIENT_TYPE_ARRANGER,
            self::RECIPIENT_TYPE_LENDERS,
            self::RECIPIENT_TYPE_RUN,
            self::RECIPIENT_TYPE_SUBMITTER
        ]);

        foreach ($recipients as $recipient) {
            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getPrenom();
                $message               = $this->messageProvider->newMessage('project-publication', $keywords);
                $message->setTo($recipient->getEmail());

                $sent += $this->mailer->send($message);
            }
        }

        return $sent;
    }

    /**
     * @param Bids $bid
     * @return int
     * @throws \Swift_RfcComplianceException
     */
    public function sendBidSubmitted(Bids $bid): int
    {
        $formatter = new \NumberFormatter('fr_FR', \NumberFormatter::DEFAULT_STYLE);
        $project   = $bid->getProject();
        $keywords  = [
            'firstName'     => '',
            'projectUrl'    => $this->router->generate('demo_project_details', ['hash' => $project->getHash()], RouterInterface::ABSOLUTE_URL),
            'projectName'   => $project->getIdCompany()->getName() . ' / ' . $project->getTitle(),
            'bidderName'    => $bid->getWallet()->getIdClient()->getCompany()->getName(),
            'bidAmount'     => $formatter->format($bid->getAmount()),
            'bidRateIndex'  => $bid->getRate()->getIndexType(),
            'bidMarginRate' => $formatter->format($bid->getRate()->getMargin()),
            'bidAgent'      => $bid->isAgent() ? 'oui' : 'non'
        ];

        $sent       = 0;
        $recipients = $this->getProjectRecipients($project, [
            self::RECIPIENT_TYPE_ARRANGER,
            self::RECIPIENT_TYPE_SUBMITTER
        ]);

        foreach ($recipients as $recipient) {
            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getPrenom();
                $message               = $this->messageProvider->newMessage('bid-new', $keywords);
                $message->setTo($recipient->getEmail());

                $sent += $this->mailer->send($message);
            }
        }

        return $sent;
    }

    /**
     * @param Bids $bid
     * @return int
     * @throws \Swift_RfcComplianceException
     */
    public function sendBidAcceptedRejected(Bids $bid): int
    {
        $recipient = $bid->getWallet()->getIdClient();

        if (empty($recipient->getEmail())) {
            return 0;
        }

        $project  = $bid->getProject();
        $mailType = $bid->getStatus() === Bids::STATUS_ACCEPTED ? 'bid-accepted' : 'bid-rejected';
        $message  = $this->messageProvider->newMessage($mailType, [
            'firstName'   => $recipient->getPrenom(),
            'projectUrl'  => $this->router->generate('demo_lender_project_details', ['slug' => $project->getSlug()], RouterInterface::ABSOLUTE_URL),
            'projectName' => $project->getIdCompany()->getName() . ' / ' . $project->getTitle()
        ]);

        $message->setTo($recipient->getEmail());

        return $this->mailer->send($message);
    }

    /**
     * @param Projects $project
     * @return int
     * @throws \Swift_RfcComplianceException
     */
    public function sendProjectFundingEnd(Projects $project): int
    {
        $keywords = [
            'firstName'    => '',
            'projectUrl'   => $this->router->generate('demo_project_details', ['hash' => $project->getHash()], RouterInterface::ABSOLUTE_URL),
            'projectName'  => $project->getIdCompany()->getName() . ' / ' . $project->getTitle(),
            'signatureUrl' => ''
        ];

        $sent         = 0;
        $recipients   = $this->getProjectRecipients($project, [self::RECIPIENT_TYPE_ARRANGER]);
        $acceptedBids = $this->entityManager->getRepository(Bids::class)->findBy([
            'project' => $project,
            'status'  => Bids::STATUS_ACCEPTED
        ]);

        foreach ($acceptedBids as $acceptedBid) {
            $recipients[$acceptedBid->getWallet()->getIdClient()->getIdClient()] = $acceptedBid->getWallet()->getIdClient();
        }

        foreach ($recipients as $recipient) {
            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getPrenom();
                $message               = $this->messageProvider->newMessage('project-funding-end', $keywords);
                $message->setTo($recipient->getEmail());

                $sent += $this->mailer->send($message);
            }
        }

        return $sent;
    }

    /**
     * @param Projects $project
     * @param array    $types
     * @return Clients[]
     */
    private function getProjectRecipients(Projects $project, array $types): array
    {
        $recipients = [];

        foreach ($types as $type) {
            switch ($type) {
                case self::RECIPIENT_TYPE_ARRANGER:
                    if ($arranger = $project->getRunParticipant()) {
                        $recipients[$arranger->getCompany()->getIdClientOwner()->getIdClient()] = $arranger->getCompany()->getIdClientOwner();
                    }
                    break;
                case self::RECIPIENT_TYPE_LENDERS:
                    $lenders = $project->getLenders();
                    foreach ($lenders as $lender) {
                        $recipients[$lender->getIdClientOwner()->getIdClient()] = $lender->getIdClientOwner();
                    }
                    break;
                case self::RECIPIENT_TYPE_RUN:
                    if ($run = $project->getArrangerParticipant()) {
                        $recipients[$run->getCompany()->getIdClientOwner()->getIdClient()] = $run->getCompany()->getIdClientOwner();
                    }
                    break;
                case self::RECIPIENT_TYPE_SUBMITTER:
                    if ($submitter = $project->getIdClientSubmitter()) {
                        $recipients[$submitter->getIdClient()] = $submitter;
                    }
                    break;
            }
        }

        return $recipients;
    }
}
