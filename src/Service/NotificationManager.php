<?php

declare(strict_types=1);

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Swift_RfcComplianceException;
use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Entity\{Bids, Clients, ClientsStatus, Notification, Project, ProjectComment};
use Unilend\Message\Client\ClientInvited;
use Unilend\Repository\ClientsStatusHistoryRepository;
use Unilend\Repository\NotificationRepository;
use Unilend\Repository\ProjectInvitationRepository;

class NotificationManager
{
    public const RECIPIENT_TYPE_AGENCY       = 'agency';
    public const RECIPIENT_TYPE_ARRANGER     = 'arranger';
    public const RECIPIENT_TYPE_LENDERS      = 'lenders';
    public const RECIPIENT_TYPE_RUN          = 'run';
    public const RECIPIENT_TYPE_SUBMITTER    = 'submitter';
    public const RECIPIENT_TYPES_BACK_OFFICE = [
        self::RECIPIENT_TYPE_AGENCY,
        self::RECIPIENT_TYPE_ARRANGER,
        self::RECIPIENT_TYPE_RUN,
        self::RECIPIENT_TYPE_SUBMITTER,
    ];

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var NotificationRepository */
    private $notificationRepository;
    /** @var MailerManager */
    private $mailerManager;
    /** @var MessageBusInterface */
    private $messageBus;
    /** @var ClientsStatusHistoryRepository */
    private $clientsStatusHistoryRepository;
    /** @var ProjectInvitationRepository */
    private $projectInvitationRepository;

    /**
     * @param EntityManagerInterface         $entityManager
     * @param NotificationRepository         $notificationRepository
     * @param MailerManager                  $mailerManager
     * @param MessageBusInterface            $messageBus
     * @param ClientsStatusHistoryRepository $clientsStatusHistoryRepository
     * @param ProjectInvitationRepository    $projectInvitationRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository,
        MailerManager $mailerManager,
        MessageBusInterface $messageBus,
        ClientsStatusHistoryRepository $clientsStatusHistoryRepository,
        ProjectInvitationRepository $projectInvitationRepository
    ) {
        $this->entityManager                  = $entityManager;
        $this->notificationRepository         = $notificationRepository;
        $this->mailerManager                  = $mailerManager;
        $this->messageBus                     = $messageBus;
        $this->clientsStatusHistoryRepository = $clientsStatusHistoryRepository;
        $this->projectInvitationRepository    = $projectInvitationRepository;
    }

    /**
     * @param Clients $client
     */
    public function createAccountCreated(Clients $client): void
    {
        $this->createNotification(Notification::TYPE_ACCOUNT_CREATED, [$client]);
    }

    /**
     * @param Project $project
     *
     * @throws Swift_RfcComplianceException
     * @throws Exception
     */
    public function createProjectRequest(Project $project): void
    {
        $recipients = $this->getProjectRecipients($project, [
            self::RECIPIENT_TYPE_SUBMITTER,
            self::RECIPIENT_TYPE_ARRANGER,
            self::RECIPIENT_TYPE_RUN,
        ]);

        $this->createNotification(Notification::TYPE_PROJECT_REQUEST, $recipients, $project);

        $this->mailerManager->sendProjectRequest($project, $recipients);
    }

    /**
     * @param Project $project
     *
     * @throws Swift_RfcComplianceException
     * @throws Exception
     */
    public function createProjectPublication(Project $project): void
    {
        $recipients = $this->getProjectRecipients($project);

        $this->createNotification(Notification::TYPE_PROJECT_PUBLICATION, $recipients, $project);

        foreach ($recipients as $recipient) {
            if (ClientsStatus::STATUS_CREATION === $this->clientsStatusHistoryRepository->findActualStatus($recipient)) {
                $inviter = $this->projectInvitationRepository->findOneBy(['client' => $recipient, 'project' => $project])->getInvitedBy();
                $this->messageBus->dispatch(new ClientInvited($inviter->getIdClient(), $recipient->getIdClient(), $project->getId()));
            } else {
                $this->mailerManager->sendProjectPublication($project, $recipients);
            }
        }
    }

    /**
     * @param Bids $bid
     *
     * @throws Swift_RfcComplianceException
     * @throws Exception
     */
    public function createBidSubmitted(Bids $bid): void
    {
        $bidder     = $bid->getLender()->getIdClientOwner();
        $recipients = $this->getProjectRecipients($bid->getTranche()->getProject());

        unset($recipients[$bidder->getIdClient()]);

        $this->createNotification(Notification::TYPE_BID_SUBMITTED_BIDDER, [$bidder], null, $bid);
        $this->createNotification(Notification::TYPE_BID_SUBMITTED_LENDERS, $recipients, null, $bid);

        $this->mailerManager->sendBidSubmitted($bid, $recipients);
    }

    /**
     * @param ProjectComment $comment
     *
     * @throws Exception
     */
    public function createProjectCommentAdded(ProjectComment $comment): void
    {
        $recipients = $this->getProjectRecipients($comment->getProject());

        unset($recipients[$comment->getClient()->getIdClient()]);

        foreach ($recipients as $recipient) {
            $notification = $this->notificationRepository->findOneBy([
                'client'  => $recipient,
                'project' => $comment->getProject(),
                'type'    => Notification::TYPE_PROJECT_COMMENT_ADDED,
                'status'  => Notification::STATUS_UNREAD,
            ]);

            if (null === $notification) {
                $this->createNotification(Notification::TYPE_PROJECT_COMMENT_ADDED, [$recipient], $comment->getProject());

                $this->mailerManager->sendProjectCommentAdded($comment, [$recipient]);
            }
        }
    }

    /**
     * @todo change when users' roles are better defined
     *
     * @param Project    $project
     * @param array|null $types
     *
     * @throws Exception
     *
     * @return Clients[]
     */
    private function getProjectRecipients(Project $project, ?array $types = null): array
    {
        $recipients = [];

        if (null === $types) {
            foreach ($project->getProjectParticipants() as $projectParticipant) {
                if ($projectParticipant->getClient()) {
                    $recipients[$projectParticipant->getClient()->getIdClient()] = $projectParticipant->getClient();
                }
                $recipients[$projectParticipant->getCompany()->getIdClientOwner()->getIdClient()] = $projectParticipant->getCompany()->getIdClientOwner();
            }

            return $recipients;
        }

        foreach ($types as $type) {
            switch ($type) {
                case self::RECIPIENT_TYPE_ARRANGER:
                    if ($arranger = $project->getArranger()) {
                        $recipients[$arranger->getCompany()->getIdClientOwner()->getIdClient()] = $arranger->getCompany()->getIdClientOwner();
                    }

                    if ($deputyArranger = $project->getDeputyArranger()) {
                        $recipients[$deputyArranger->getCompany()->getIdClientOwner()->getIdClient()] = $deputyArranger->getCompany()->getIdClientOwner();
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
                case self::RECIPIENT_TYPE_AGENCY:
                    if ($loanOfficer = $project->getLoanOfficer()) {
                        $recipients[$loanOfficer->getCompany()->getIdClientOwner()->getIdClient()] = $loanOfficer->getCompany()->getIdClientOwner();
                    }

                    if ($securityTrustee = $project->getSecurityTrustee()) {
                        $recipients[$securityTrustee->getCompany()->getIdClientOwner()->getIdClient()] = $securityTrustee->getCompany()->getIdClientOwner();
                    }

                    break;
            }
        }

        return $recipients;
    }

    /**
     * @param int          $type
     * @param Clients[]    $clients
     * @param Project|null $project
     * @param Bids|null    $bid
     */
    private function createNotification(int $type, array $clients, ?Project $project = null, ?Bids $bid = null): void
    {
        foreach ($clients as $client) {
            $notification = new Notification();
            $notification
                ->setType($type)
                ->setStatus(Notification::STATUS_UNREAD)
                ->setClient($client)
                ->setProject($project)
                ->setBid($bid)
            ;

            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();
    }
}
