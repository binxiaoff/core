<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\{Method, Security};
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, WalletType};

class NotificationsController extends Controller
{
    /**
     * @Route("/notifications/update", name="notifications_update")
     * @Security("has_role('ROLE_LENDER')")
     * @Method("POST")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return JsonResponse
     */
    public function updateAction(Request $request, ?UserInterface $client): JsonResponse
    {
        $action = $request->request->get('action');
        $list   = $request->request->get('list');

        if (false === in_array($action, ['read', 'all_read'])) {
            return new JsonResponse([
                'errors' => [
                    'details' => 'Unknown action',
                ]
            ]);
        }

        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $notificationsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Notifications');
        $wallet                  = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        switch ($action) {
            case 'all_read':
                $notificationsRepository->markAllLenderNotificationsAsRead($wallet->getId());
                break;
            case 'read':
                if (false === is_array($list)) {
                    return new JsonResponse([
                        'error' => [
                            'details' => 'Invalid list of IDs',
                        ]
                    ]);
                }
                $notificationsRepository->markLenderNotificationsAsRead($wallet->getId(), $list);
                break;
        }

        return new JsonResponse([
            'success' => true
        ]);
    }

    /**
     * @Route("/notifications/pagination", name="notifications_pagination")
     * @Security("has_role('ROLE_LENDER')")
     * @Method("GET")
     *
     * @param request                    $request
     * @param userinterface|clients|null $client
     *
     * @return JsonResponse
     */
    public function paginationAction(Request $request, ?UserInterface $client): JsonResponse
    {
        $perPage     = $request->query->getInt('perPage');
        $currentPage = $request->query->getInt('currentPage');

        if (empty($perPage) || empty($currentPage) || $perPage < 1 || $currentPage < 1) {
            return new JsonResponse([
                'error' => [
                    'details' => 'Invalid arguments',
                ]
            ]);
        }

        $notificationsDisplayManager = $this->get('unilend.frontbundle.notification_display_manager');
        $start                       = $perPage * ($currentPage - 1) + 1;
        $length                      = $perPage;

        return new JsonResponse([
            'notifications' => $notificationsDisplayManager->getLenderNotifications($client, $start, $length),
            'pagination'    => [
                'perPage'     => $perPage,
                'currentPage' => $currentPage
            ]
        ]);
    }
}
