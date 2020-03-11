<?php

declare(strict_types=1);

namespace Unilend\Service;

use Doctrine\ORM\{EntityManagerInterface, ORMException, OptimisticLockException};
use Exception;
use Unilend\Entity\{Clients, Notification, Project, ProjectComment, ProjectStatus, TrancheOffer};
use Unilend\Repository\NotificationRepository;

class NotificationManager
{
    public const RECIPIENT_TYPE_AGENCY    = 'agency';
    public const RECIPIENT_TYPE_LENDERS   = 'lenders';
    public const RECIPIENT_TYPE_RUN       = 'run';
    public const RECIPIENT_TYPE_SUBMITTER = 'submitter';

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var NotificationRepository */
    private $notificationRepository;
    /** @var MailerManager */
    private $mailerManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param NotificationRepository $notificationRepository
     * @param MailerManager          $mailerManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository,
        MailerManager $mailerManager
    ) {
        $this->entityManager          = $entityManager;
        $this->notificationRepository = $notificationRepository;
        $this->mailerManager          = $mailerManager;
    }

    /**
     * @param Project $project
     * @param Clients $client
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createProjectPublication(Project $project, Clients $client): void
    {
        if (ProjectStatus::STATUS_PUBLISHED === $project->getCurrentStatus()->getStatus()) {
            $notification = $this->buildNotification(Notification::TYPE_PROJECT_PUBLICATION, $client, $project);

            $this->notificationRepository->save($notification);
        }
    }

    /**
     * @param TrancheOffer $trancheOffer
     *
     * @throws Exception
     */
    public function createTrancheOfferSubmitted(TrancheOffer $trancheOffer): void
    {
        $trancheOfferMaker = $trancheOffer->getAddedBy()->getClient();
        $recipients        = $this->getProjectRecipients($trancheOffer->getTranche()->getProject());

        unset($recipients[$trancheOfferMaker->getId()]);

        $this->createNotification(Notification::TYPE_TRANCHE_OFFER_SUBMITTED_SUBMITTER, [$trancheOfferMaker], null, $trancheOffer);
        $this->createNotification(Notification::TYPE_TRANCHE_OFFER_SUBMITTED_PARTICIPANTS, $recipients, null, $trancheOffer);

        $this->mailerManager->sendTrancheOfferSubmitted($trancheOffer, $recipients);
    }

    /**
     * @param ProjectComment $comment
     *
     * @throws Exception
     */
    public function createProjectCommentAdded(ProjectComment $comment): void
    {
        $recipients = $this->getProjectRecipients($comment->getProject());

        unset($recipients[$comment->getClient()->getId()]);

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
    private function getProjectRecipients(Project $project, array $types = []): array
    {
        $recipients = [];

        if (null === $types) {
            foreach ($project->getProjectParticipations() as $projectParticipation) {
                if ($projectParticipation->getClient()) {
                    $recipients[$projectParticipation->getClient()->getId()] = $projectParticipation->getClient();
                }
                $recipients[$projectParticipation->getCompany()->getIdClientOwner()->getId()] = $projectParticipation->getCompany()->getIdClientOwner();
            }

            return $recipients;
        }

        foreach ($types as $type) {
            switch ($type) {
                case self::RECIPIENT_TYPE_LENDERS:
                    $lenders = $project->getParticipants();
                    foreach ($lenders as $lender) {
                        $recipients[$lender->getCompany()->getIdClientOwner()->getId()] = $lender->getCompany()->getIdClientOwner();
                    }

                    break;
                case self::RECIPIENT_TYPE_RUN:
                    if ($run = $project->getRun()) {
                        $recipients[$run->getCompany()->getIdClientOwner()->getId()] = $run->getCompany()->getIdClientOwner();
                    }

                    break;
                case self::RECIPIENT_TYPE_SUBMITTER:
                    if ($submitter = $project->getSubmitterClient()) {
                        $recipients[$submitter->getId()] = $submitter;
                    }

                    break;
                case self::RECIPIENT_TYPE_AGENCY:
                    if ($loanOfficer = $project->getLoanOfficer()) {
                        $recipients[$loanOfficer->getCompany()->getIdClientOwner()->getId()] = $loanOfficer->getCompany()->getIdClientOwner();
                    }

                    if ($securityTrustee = $project->getSecurityTrustee()) {
                        $recipients[$securityTrustee->getCompany()->getIdClientOwner()->getId()] = $securityTrustee->getCompany()->getIdClientOwner();
                    }

                    break;
            }
        }

        return $recipients;
    }

    /**
     * @param int               $type
     * @param Clients[]         $clients
     * @param Project|null      $project
     * @param TrancheOffer|null $trancheOffer
     */
    private function createNotification(int $type, array $clients, ?Project $project = null, ?TrancheOffer $trancheOffer = null): void
    {
        foreach ($clients as $client) {
            $notification = $this->buildNotification($type, $client, $project, $trancheOffer);
            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();
    }

    /**
     * @param int               $type
     * @param Clients           $client
     * @param Project|null      $project
     * @param TrancheOffer|null $trancheOffer
     *
     * @return Notification
     */
    private function buildNotification(int $type, Clients $client, ?Project $project = null, ?TrancheOffer $trancheOffer = null): Notification
    {
        return (new Notification())
            ->setType($type)
            ->setStatus(Notification::STATUS_UNREAD)
            ->setClient($client)
            ->setProject($project)
            ->setTrancheOffer($trancheOffer)
        ;
    }
}
