<?php

declare(strict_types=1);

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use NumberFormatter;
use Swift_Mailer;
use Swift_RfcComplianceException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{AttachmentSignature, Bids, Clients, Loans, Project, Projects};
use Unilend\SwiftMailer\TemplateMessageProvider;

class DemoMailerManager
{
    public const RECIPIENT_TYPE_ARRANGER  = 'arranger';
    public const RECIPIENT_TYPE_LENDERS   = 'lenders';
    public const RECIPIENT_TYPE_RUN       = 'run';
    public const RECIPIENT_TYPE_SUBMITTER = 'submitter';

    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var Swift_Mailer */
    private $mailer;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var RouterInterface */
    private $router;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TemplateMessageProvider $messageProvider
     * @param Swift_Mailer            $mailer
     * @param EntityManagerInterface  $entityManager
     * @param RouterInterface         $router
     * @param TranslatorInterface     $translator
     */
    public function __construct(
        TemplateMessageProvider $messageProvider,
        Swift_Mailer $mailer,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        TranslatorInterface $translator
    ) {
        $this->messageProvider = $messageProvider;
        $this->mailer          = $mailer;
        $this->entityManager   = $entityManager;
        $this->router          = $router;
        $this->translator      = $translator;
    }

    /**
     * @param Project $project
     *
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function sendProjectRequest(Project $project): int
    {
        $keywords = [
            'firstName'  => '',
            'projectUrl' => $this->router->generate('edit_project_details', ['hash' => $project->getHash()], RouterInterface::ABSOLUTE_URL),
            'borrower'   => $project->getBorrowerCompany()->getName(),
        ];

        $sent       = 0;
        $recipients = $this->getProjectRecipients($project, [
            self::RECIPIENT_TYPE_ARRANGER,
            self::RECIPIENT_TYPE_RUN,
            self::RECIPIENT_TYPE_SUBMITTER,
        ]);

        foreach ($recipients as $recipient) {
            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getFirstName();
                $message               = $this->messageProvider->newMessage('project-new', $keywords);
                $message->setTo($recipient->getEmail());

                $sent += $this->mailer->send($message);
            }
        }

        return $sent;
    }

    /**
     * @param Project $project
     *
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function sendScoringUpdated(Project $project): int
    {
        $keywords = [
            'firstName'    => '',
            'projectUrl'   => $this->router->generate('edit_project_details', ['hash' => $project->getHash()], RouterInterface::ABSOLUTE_URL),
            'projectName'  => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
            'scoringValue' => $project->getInternalRatingScore(),
        ];

        $sent       = 0;
        $recipients = $this->getProjectRecipients($project, [
            self::RECIPIENT_TYPE_ARRANGER,
            self::RECIPIENT_TYPE_RUN,
            self::RECIPIENT_TYPE_SUBMITTER,
        ]);

        foreach ($recipients as $recipient) {
            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getFirstName();
                $message               = $this->messageProvider->newMessage('project-scoring', $keywords);
                $message->setTo($recipient->getEmail());

                $sent += $this->mailer->send($message);
            }
        }

        return $sent;
    }

    /**
     * @param Project $project
     *
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function sendProjectPublication(Project $project): int
    {
        $keywords = [
            'firstName'   => '',
            'projectUrl'  => $this->router->generate('lender_project_details', ['slug' => $project->getSlug()], RouterInterface::ABSOLUTE_URL),
            'projectName' => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
        ];

        $sent       = 0;
        $recipients = $this->getProjectRecipients($project, [
            self::RECIPIENT_TYPE_ARRANGER,
            self::RECIPIENT_TYPE_LENDERS,
            self::RECIPIENT_TYPE_RUN,
            self::RECIPIENT_TYPE_SUBMITTER,
        ]);

        foreach ($recipients as $recipient) {
            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getFirstName();
                $message               = $this->messageProvider->newMessage('project-publication', $keywords);
                $message->setTo($recipient->getEmail());

                $sent += $this->mailer->send($message);
            }
        }

        return $sent;
    }

    /**
     * @param Bids $bid
     *
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function sendBidSubmitted(Bids $bid): int
    {
        $formatter = new NumberFormatter('fr_FR', NumberFormatter::DEFAULT_STYLE);
        $project   = $bid->getProject();
        $keywords  = [
            'firstName'     => '',
            'projectUrl'    => $this->router->generate('edit_project_details', ['hash' => $project->getHash()], RouterInterface::ABSOLUTE_URL),
            'projectName'   => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
            'bidderName'    => $bid->getWallet()->getIdClient()->getCompany()->getName(),
            'bidAmount'     => $formatter->format($bid->getAmount()),
            'bidRateIndex'  => $bid->getRate()->getIndexType(),
            'bidMarginRate' => $formatter->format($bid->getRate()->getMargin()),
        ];

        $sent       = 0;
        $recipients = $this->getProjectRecipients($project, [
            self::RECIPIENT_TYPE_ARRANGER,
            self::RECIPIENT_TYPE_SUBMITTER,
        ]);

        foreach ($recipients as $recipient) {
            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getFirstName();
                $message               = $this->messageProvider->newMessage('bid-new', $keywords);
                $message->setTo($recipient->getEmail());

                $sent += $this->mailer->send($message);
            }
        }

        return $sent;
    }

    /**
     * @param Bids $bid
     *
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function sendBidAcceptedRejected(Bids $bid): int
    {
        $recipient = $bid->getWallet()->getIdClient();

        if (empty($recipient->getEmail())) {
            return 0;
        }

        $project  = $bid->getProject();
        $mailType = Bids::STATUS_ACCEPTED === $bid->getStatus() ? 'bid-accepted' : 'bid-rejected';
        $message  = $this->messageProvider->newMessage($mailType, [
            'firstName'   => $recipient->getFirstName(),
            'projectUrl'  => $this->router->generate('lender_project_details', ['slug' => $project->getSlug()], RouterInterface::ABSOLUTE_URL),
            'projectName' => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
        ]);

        $message->setTo($recipient->getEmail());

        return $this->mailer->send($message);
    }

    /**
     * @param Project $project
     *
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function sendProjectFundingEnd(Project $project): int
    {
        $keywords = [
            'firstName'   => '',
            'projectUrl'  => $this->router->generate('lender_project_details', ['slug' => $project->getSlug()], RouterInterface::ABSOLUTE_URL),
            'projectName' => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
        ];

        $sent  = 0;
        $loans = $this->entityManager->getRepository(Loans::class)->findBy([
            'project' => $project,
            'status'  => Loans::STATUS_PENDING,
        ]);

        foreach ($loans as $loan) {
            $recipient = $loan->getWallet()->getIdClient();

            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getFirstName();

                $message = $this->messageProvider->newMessage('project-funding-end', $keywords);
                $message->setTo($recipient->getEmail());

                $sent += $this->mailer->send($message);
            }
        }

        return $sent;
    }

    /**
     * @param Project             $project
     * @param AttachmentSignature $signature
     *
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function sendElectronicSignature(Project $project, AttachmentSignature $signature): int
    {
        $keywords = [
            'firstName'    => $signature->getSignatory()->getFirstName(),
            'projectName'  => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
            'signatureUrl' => $this->router->generate('signature_sign', ['attachment' => $signature->getAttachment()->getId()], RouterInterface::ABSOLUTE_URL),
        ];

        $message = $this->messageProvider->newMessage('document-signature', $keywords);
        $message->setTo($signature->getSignatory()->getEmail());

        return $this->mailer->send($message);
    }

    /**
     * @param Project $project
     * @param array   $types
     *
     * @return Clients[]
     */
    private function getProjectRecipients(Project $project, array $types): array
    {
        $recipients = [];

        foreach ($types as $type) {
            switch ($type) {
                case self::RECIPIENT_TYPE_ARRANGER:
                    if ($arranger = $project->getArranger()) {
                        $recipients[$arranger->getCompany()->getIdClientOwner()->getIdClient()] = $arranger->getCompany()->getIdClientOwner();
                    }

                    break;
                case self::RECIPIENT_TYPE_LENDERS:
                    $lenders = $project->getLenders();
                    foreach ($lenders as $lender) {
                        $recipients[$lender->getCompany()->getIdClientOwner()->getIdClient()] = $lender->getCompany()->getIdClientOwner();
                    }

                    break;
                case self::RECIPIENT_TYPE_RUN:
                    if ($run = $project->getRun()) {
                        $recipients[$run->getCompany()->getIdClientOwner()->getIdClient()] = $run->getCompany()->getIdClientOwner();
                    }

                    break;
                case self::RECIPIENT_TYPE_SUBMITTER:
                    if ($submitter = $project->getSubmitterClient()) {
                        $recipients[$submitter->getIdClient()] = $submitter;
                    }

                    break;
            }
        }

        return $recipients;
    }
}
