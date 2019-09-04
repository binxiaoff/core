<?php

declare(strict_types=1);

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use NumberFormatter;
use Swift_Mailer;
use Swift_RfcComplianceException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{AttachmentSignature,
    Bids,
    Clients,
    Loans,
    Project,
    ProjectComment,
    ProjectParticipant,
    Tranche};
use Unilend\Repository\ClientsStatusRepository;
use Unilend\Repository\CompaniesRepository;
use Unilend\Repository\TemporaryLinksLoginRepository;
use Unilend\SwiftMailer\TemplateMessageProvider;

class MailerManager
{
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
    /** @var NumberFormatter */
    private $percentageFormatter;
    /** @var NumberFormatter */
    private $numberFormatter;
    /** @var TemporaryLinksLoginRepository */
    private $temporaryLinksLoginRepository;
    /** @var ClientsStatusRepository */
    private $clientsStatusRepository;
    /** @var CompaniesRepository */
    private $companiesRepository;
    /** @var ProjectParticipant */
    private $projectParticipant;

    /**
     * @param TemplateMessageProvider       $messageProvider
     * @param Swift_Mailer                  $mailer
     * @param EntityManagerInterface        $entityManager
     * @param RouterInterface               $router
     * @param TranslatorInterface           $translator
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     * @param ClientsStatusRepository       $clientsStatusRepository
     * @param CompaniesRepository           $companiesRepository
     * @param NumberFormatter               $numberFormatter
     * @param NumberFormatter               $percentageFormatter
     */
    public function __construct(
        TemplateMessageProvider $messageProvider,
        Swift_Mailer $mailer,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        TranslatorInterface $translator,
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository,
        ClientsStatusRepository $clientsStatusRepository,
        CompaniesRepository $companiesRepository,
        NumberFormatter $numberFormatter,
        NumberFormatter $percentageFormatter
    ) {
        $this->messageProvider               = $messageProvider;
        $this->mailer                        = $mailer;
        $this->entityManager                 = $entityManager;
        $this->percentageFormatter           = $percentageFormatter;
        $this->numberFormatter               = $numberFormatter;
        $this->router                        = $router;
        $this->translator                    = $translator;
        $this->temporaryLinksLoginRepository = $temporaryLinksLoginRepository;
        $this->clientsStatusRepository       = $clientsStatusRepository;
        $this->companiesRepository           = $companiesRepository;
    }

    /**
     * @param Clients $client
     *
     * @return int
     */
    public function sendIdentityUpdated(Clients $client): int
    {
        $sent = 0;

        $classMetaData = $this->entityManager->getClassMetadata(Clients::class);
        $unitOfWork    = $this->entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSet($classMetaData, $client);

        $changeSet = $unitOfWork->getEntityChangeSet($client);

        foreach ($changeSet as $field => $value) {
            if (('mobile' === $field || 'phone' === $field) && $changeSet[$field][0]->equals($changeSet[$field][1])) {
                unset($changeSet[$field]);
            }
        }

        if (false === empty($changeSet) && false === empty($client)) {
            foreach ($changeSet as $field => $value) {
                unset($changeSet[$field]);
                $changeSet[] = $this->translator->trans('mail-identity-updated.' . $field);
            }

            if (count($changeSet) > 1) {
                $content      = 'Ces informations personnelles ont été modifiées :';
                $changeFields = '<ul><li>';
                $changeFields .= implode('</li><li>', $changeSet);
                $changeFields .= '</li></ul>';
            } else {
                $content      = 'Cette information personnelle a été modifiée :';
                $changeFields = $changeSet[0];
            }

            $keywords = [
                'firstName'    => $client->getFirstName(),
                'content'      => $content,
                'profileUrl'   => $this->router->generate('profile', [], RouterInterface::ABSOLUTE_URL),
                'changeFields' => $changeFields,
            ];

            $message = $this->messageProvider->newMessage('identity-updated', $keywords);
            $message->setTo($client->getEmail());

            $sent += $this->mailer->send($message);

            return $sent;
        }

        return $sent;
    }

    /**
     * @param string $inviter
     * @param string $token
     * @param string $email
     *
     * @return int
     */
    public function sendInvitation(string $inviter, string $token, string $email)
    {
        $sent = 0;

        $keywords = [
            'inviterName'    => $inviter,
            'initAccountUrl' => $this->router->generate('account_init', ['securityToken' => $token], RouterInterface::ABSOLUTE_URL),
        ];

        $message = $this->messageProvider->newMessage('invite-guest', $keywords);
        $message->setTo($email);

        $sent += $this->mailer->send($message);

        return $sent;
    }

    /**
     * @param Clients $client
     * @param array   $changeSet
     *
     * @return int
     */
    public function sendIdentityUpdated(Clients $client, array $changeSet): int
    {
        $changeSet = array_map(function ($field) {
            return $this->translator->trans('mail-identity-updated.' . $field);
        }, $changeSet);
        if (count($changeSet) > 1) {
            $content      = $this->translator->trans('mail-identity-updated.content-message-plural');
            $changeFields = '<ul><li>';
            $changeFields .= implode('</li><li>', $changeSet);
            $changeFields .= '</li></ul>';
        } else {
            $content      = $this->translator->trans('mail-identity-updated.content-message-singular');
            $changeFields = $changeSet[0];
        }

        $keywords = [
            'firstName'    => $client->getFirstName(),
            'content'      => $content,
            'profileUrl'   => $this->router->generate('profile', [], RouterInterface::ABSOLUTE_URL),
            'changeFields' => $changeFields,
        ];

        $message = $this->messageProvider->newMessage('identity-updated', $keywords);
        $message->setTo($client->getEmail());

        return $this->mailer->send($message);
    }

    /**
     * @param Project   $project
     * @param Clients[] $recipients
     *
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function sendProjectPublication(Project $project, array $recipients): int
    {
        $sent     = 0;
        $keywords = [
            'firstName'   => '',
            'projectUrl'  => $this->router->generate('lender_project_details', ['slug' => $project->getSlug()], RouterInterface::ABSOLUTE_URL),
            'projectName' => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
        ];

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
     * @param ProjectComment $comment
     * @param Clients[]      $recipients
     *
     * @return int
     */
    public function sendProjectCommentAdded(ProjectComment $comment, array $recipients): int
    {
        $sent     = 0;
        $project  = $comment->getProject();
        $keywords = [
            'firstName'   => '',
            'projectUrl'  => $this->router->generate('lender_project_details', ['slug' => $project->getSlug()], RouterInterface::ABSOLUTE_URL) . '#article-discussions',
            'projectName' => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
        ];

        foreach ($recipients as $recipient) {
            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getFirstName();
                $message               = $this->messageProvider->newMessage('project-comment-added', $keywords);
                $message->setTo($recipient->getEmail());

                $sent += $this->mailer->send($message);
            }
        }

        return $sent;
    }

    /**
     * @param Bids      $bid
     * @param Clients[] $recipients
     *
     * @return int
     */
    public function sendBidSubmitted(Bids $bid, array $recipients): int
    {
        $sent     = 0;
        $project  = $bid->getTranche()->getProject();
        $keywords = [
            'firstName'     => '',
            'projectUrl'    => $this->router->generate('edit_project_details', ['hash' => $project->getHash()], RouterInterface::ABSOLUTE_URL),
            'projectName'   => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
            'bidderName'    => $bid->getLender()->getName(),
            'bidAmount'     => $this->numberFormatter->format($bid->getMoney()->getAmount()),
            'bidRateIndex'  => $bid->getRate()->getIndexType(),
            'bidMarginRate' => $this->percentageFormatter->format($bid->getRate()->getMargin()),
        ];

        foreach ($recipients as $recipient) {
            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getFirstName();
                $message               = $this->messageProvider->newMessage('bid-submitted', $keywords);
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
        // @todo change when all roles are defined
        $recipient = $bid->getAddedBy();

        if (empty($recipient->getEmail())) {
            return 0;
        }

        $project  = $bid->getTranche()->getProject();
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

        $sent     = 0;
        $tranches = $this->entityManager->getRepository(Tranche::class)->findBy(['project' => $project]);
        $loans    = $this->entityManager->getRepository(Loans::class)->findBy([
            'tranche' => $tranches,
            'status'  => Loans::STATUS_PENDING,
        ]);

        foreach ($loans as $loan) {
            // @todo change when all roles are defined
            $recipient = $loan->getLender()->getIdClientOwner();

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
}
