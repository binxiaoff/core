<?php

declare(strict_types=1);

namespace Unilend\Service\Notification;

use Exception;
use NumberFormatter;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{Clients, Notification};
use Unilend\Repository\NotificationRepository;

class NotificationDisplayManager
{
    /** @var NotificationRepository */
    private $notificationRepository;
    /** @var TranslatorInterface */
    private $translator;
    /** @var RouterInterface */
    private $router;
    /** @var NumberFormatter */
    private $currencyFormatterNoDecimal;

    /**
     * @param NotificationRepository $notificationRepository
     * @param TranslatorInterface    $translator
     * @param RouterInterface        $router
     * @param NumberFormatter        $currencyFormatterNoDecimal
     */
    public function __construct(
        NotificationRepository $notificationRepository,
        TranslatorInterface $translator,
        RouterInterface $router,
        NumberFormatter $currencyFormatterNoDecimal
    ) {
        $this->notificationRepository     = $notificationRepository;
        $this->translator                 = $translator;
        $this->router                     = $router;
        $this->currencyFormatterNoDecimal = $currencyFormatterNoDecimal;
    }

    /**
     * @param Clients  $client
     * @param int|null $limit
     * @param int|null $offset
     *
     * @throws Exception
     *
     * @return array|mixed[][]
     */
    public function getLastClientNotifications(Clients $client, ?int $limit = 20, ?int $offset = null): array
    {
        $formattedNotifications = [];
        $notifications          = $this->notificationRepository->findBy(['client' => $client], ['added' => 'DESC'], $limit, $offset);

        foreach ($notifications as $notification) {
            $type    = ''; // Style of title (account, offer-accepted, offer-rejected, normal)
            $image   = ''; // SVG icon (icons/notification)
            $title   = '';
            $content = '';

            switch ($notification->getType()) {
                case Notification::TYPE_ACCOUNT_CREATED:
                    $type    = 'normal';
                    $image   = 'circle-accepted';
                    $title   = $this->translator->trans('notifications.account-created-title');
                    $content = $this->translator->trans('notifications.account-created-content');

                    break;
                case Notification::TYPE_PROJECT_REQUEST:
                    $project = $notification->getProject();
                    $type    = 'normal';
                    $image   = 'project-added';
                    $title   = $this->translator->trans('notifications.project-request-title');
                    $content = $this->translator->trans('notifications.project-request-content', [
                        '%projectUrl%'    => '', // TODO Create a router
                        '%projectTitle%'  => $project->getTitle(),
                        '%borrowerName%'  => $project->getRiskGroupName(),
                        '%submitterName%' => $project->getSubmitterCompany()->getDisplayName(),
                    ]);

                    break;
                case Notification::TYPE_PROJECT_PUBLICATION:
                    $project = $notification->getProject();
                    $type    = 'normal';
                    $image   = 'project';
                    $title   = $this->translator->trans('notifications.project-publication-title');
                    $content = $this->translator->trans('notifications.project-publication-content', [
                        '%projectUrl%'   => '', // TODO Create a router
                        '%projectTitle%' => $project->getTitle(),
                        '%borrowerName%' => $project->getRiskGroupName(),
                    ]);

                    break;
                case Notification::TYPE_TRANCHE_OFFER_SUBMITTED_SUBMITTER:
                    $projectParticipationTranche = $notification->getProjectParticipationTranche();
                    if ($projectParticipationTranche) {
                        $project = $projectParticipationTranche->getTranche()->getProject();
                        $type    = 'normal';
                        $image   = 'offer';
                        $title   = $this->translator->trans('notifications.tranche-offer-submitted-maker-title');
                        $content = $this->translator->trans('notifications.tranche-offer-submitted-maker-content', [
                            '%projectUrl%'   => '', // TODO Create a router
                            '%projectTitle%' => $project->getTitle(),
                            '%borrowerName%' => $project->getRiskGroupName(),
                            '%offerAmount%'  => $this->currencyFormatterNoDecimal
                                ->formatCurrency(
                                    (float) $projectParticipationTranche->getInvitationReply()->getMoney()->getAmount(),
                                    $projectParticipationTranche->getInvitationReply()->getMoney()->getCurrency()
                                ),
                        ]);
                    }

                    break;
                case Notification::TYPE_TRANCHE_OFFER_SUBMITTED_PARTICIPANTS:
                    $projectParticipationTranche = $notification->getProjectParticipationTranche();
                    if ($projectParticipationTranche) {
                        $project = $projectParticipationTranche->getTranche()->getProject();
                        $type    = 'normal';
                        $image   = 'offer';
                        $title   = $this->translator->trans('notifications.tranche-offer-submitted-participants-title');
                        $content = $this->translator->trans('notifications.tranche-offer-submitted-participants-content', [
                            '%projectUrl%'     => '', // TODO Create a router
                            '%projectTitle%'   => $project->getTitle(),
                            '%borrowerName%'   => $project->getRiskGroupName(),
                            '%offerMakerName%' => $projectParticipationTranche->getProjectParticipation()->getParticipant(),
                            '%offerAmount%'    => $this->currencyFormatterNoDecimal
                                ->formatCurrency(
                                    (float) $projectParticipationTranche->getInvitationReply()->getMoney()->getAmount(),
                                    $projectParticipationTranche->getInvitationReply()->getMoney()->getCurrency()
                                ),
                        ]);
                    }

                    break;
                case Notification::TYPE_PROJECT_COMMENT_ADDED:
                    $project = $notification->getProject();
                    $type    = 'normal';
                    $image   = '';
                    $title   = $this->translator->trans('notifications.project-comment-added-title');
                    $content = $this->translator->trans('notifications.project-comment-added-content', [
                        '%projectUrl%'   => '', // TODO Create a router
                        '%projectTitle%' => $project->getTitle(),
                        '%borrowerName%' => $project->getRiskGroupName(),
                    ]);

                    break;
            }

            $formattedNotifications[] = [
                'id'        => $notification->getId(),
                'projectId' => $notification->getProject() ? $notification->getProject()->getId() : null,
                'type'      => $type,
                'title'     => $title,
                'datetime'  => $notification->getAdded(),
                'iso-8601'  => $notification->getAdded()->format('c'),
                'content'   => $content,
                'image'     => $image,
                'status'    => Notification::STATUS_READ === $notification->getStatus() ? 'read' : 'unread',
            ];
        }

        return $formattedNotifications;
    }
}
