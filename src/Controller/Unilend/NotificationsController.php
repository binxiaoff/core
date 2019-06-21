<?php

namespace Unilend\Controller\Unilend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\Clients;
use Unilend\Repository\NotificationRepository;

class NotificationsController extends AbstractController
{
    /**
     * @Route("/notifications/update", name="notifications_update", methods={"POST"})
     *
     * @param NotificationRepository     $notificationRepository
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     *
     * @return JsonResponse
     */
    public function update(NotificationRepository $notificationRepository, Request $request, ?UserInterface $user): JsonResponse
    {
        $action = $request->request->get('action');
        $list   = $request->request->get('list');

        if (false === in_array($action, ['read', 'all_read'])) {
            return new JsonResponse([
                'errors' => [
                    'details' => 'Unknown action',
                ],
            ]);
        }

        switch ($action) {
            case 'all_read':
                $notificationRepository->markAllClientNotificationsAsRead($user);

                break;
            case 'read':
                if (false === is_array($list)) {
                    return new JsonResponse([
                        'error' => [
                            'details' => 'Invalid list of IDs',
                        ],
                    ]);
                }

                $notificationRepository->markAsRead($user, $list);

                break;
        }

        return new JsonResponse([
            'success' => true,
        ]);
    }
}
