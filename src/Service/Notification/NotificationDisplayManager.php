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
     * @param Clients $client
     *
     * @throws Exception
     *
     * @return array
     */
    public function getLastClientNotifications(Clients $client)
    {
        return $this->getClientNotifications($client, 0, 20);
    }

    /**
     * @param Clients $client
     * @param int     $offset
     * @param int     $limit
     *
     * @throws Exception
     *
     * @return array
     */
    public function getClientNotifications(Clients $client, $offset = null, $limit = null)
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
                        '%projectUrl%'    => $this->router->generate('project_detail', ['projectSlug' => $project->getSlug()]),
                        '%projectTitle%'  => $project->getTitle(),
                        '%borrowerName%'  => $project->getBorrowerCompany()->getName(),
                        '%submitterName%' => $project->getSubmitterCompany()->getName(),
                    ]);

                    break;
                case Notification::TYPE_PROJECT_PUBLICATION:
                    $project = $notification->getProject();
                    $type    = 'normal';
                    $image   = 'project';
                    $title   = $this->translator->trans('notifications.project-publication-title');
                    $content = $this->translator->trans('notifications.project-publication-content', [
                        '%projectUrl%'   => $this->router->generate('lender_project_details', ['slug' => $project->getSlug()]),
                        '%projectTitle%' => $project->getTitle(),
                        '%borrowerName%' => $project->getBorrowerCompany()->getName(),
                    ]);

                    break;
                case Notification::TYPE_BID_SUBMITTED_BIDDER:
                    $bid     = $notification->getBid();
                    $project = $bid->getTranche()->getProject();
                    $type    = 'normal';
                    $image   = 'offer';
                    $title   = $this->translator->trans('notifications.bid-submitted-bidder-title');
                    $content = $this->translator->trans('notifications.bid-submitted-bidder-content', [
                        '%projectUrl%'   => $this->router->generate('lender_project_details', ['slug' => $project->getSlug()]),
                        '%projectTitle%' => $project->getTitle(),
                        '%borrowerName%' => $project->getBorrowerCompany()->getName(),
                        '%bidAmount%'    => $this->currencyFormatterNoDecimal->formatCurrency($bid->getMoney()->getAmount(), $bid->getMoney()->getCurrency()),
                    ]);

                    break;
                case Notification::TYPE_BID_SUBMITTED_LENDERS:
                    $bid     = $notification->getBid();
                    $project = $bid->getTranche()->getProject();
                    $type    = 'normal';
                    $image   = 'offer';
                    $title   = $this->translator->trans('notifications.bid-submitted-lenders-title');
                    $content = $this->translator->trans('notifications.bid-submitted-lenders-content', [
                        '%projectUrl%'   => $this->router->generate('lender_project_details', ['slug' => $project->getSlug()]),
                        '%projectTitle%' => $project->getTitle(),
                        '%borrowerName%' => $project->getBorrowerCompany()->getName(),
                        '%bidderName%'   => $bid->getLender()->getName(),
                        '%bidAmount%'    => $this->currencyFormatterNoDecimal->formatCurrency($bid->getMoney()->getAmount(), $bid->getMoney()->getCurrency()),
                    ]);

                    break;
                case Notification::TYPE_PROJECT_COMMENT_ADDED:
                    $project = $notification->getProject();
                    $type    = 'normal';
                    $image   = '';
                    $title   = $this->translator->trans('notifications.project-comment-added-title');
                    $content = $this->translator->trans('notifications.project-comment-added-content', [
                        '%projectUrl%'   => $this->router->generate('lender_project_details', ['slug' => $project->getSlug()]) . '#article-discussions',
                        '%projectTitle%' => $project->getTitle(),
                        '%borrowerName%' => $project->getBorrowerCompany()->getName(),
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
