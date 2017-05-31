<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Unilend\Bundle\CoreBusinessBundle\Repository\NotificationsRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\Bundle\FrontBundle\Service\NotificationDisplayManager;

class NotificationsController extends Controller
{
    /**
     * @Route("/notifications/update", name="notifications_update")
     * @Security("has_role('ROLE_LENDER')")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(Request $request)
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

        /** @var UserLender $user */
        $user = $this->getUser();
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var NotificationsRepository $notificationsRepository */
        $notificationsRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Notifications');
        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        $lender->get($user->getClientId(), 'id_client_owner');

        switch ($action) {
            case 'all_read':
                $notificationsRepository->markAllLenderNotificationsAsRead($lender->id_lender_account);
                break;
            case 'read':
                if (false === is_array($list)) {
                    return new JsonResponse([
                        'error' => [
                            'details' => 'Invalid list of IDs',
                        ]
                    ]);
                }
                $notificationsRepository->markLenderNotificationsAsRead($lender->id_lender_account, $list);
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
     * @param Request $request
     * @return JsonResponse
     */
    public function paginationAction(Request $request)
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

        /** @var UserLender $user */
        $user = $this->getUser();
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var NotificationDisplayManager $notificationsDisplayManager */
        $notificationsDisplayManager = $this->get('unilend.frontbundle.notification_display_manager');
        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        $lender->get($user->getClientId(), 'id_client_owner');

        $start  = $perPage * ($currentPage - 1) + 1;
        $length = $perPage;

        return new JsonResponse([
            'notifications' => $notificationsDisplayManager->getLenderNotifications($lender, $start, $length),
            'pagination'    => [
                'perPage'     => $perPage,
                'currentPage' => $currentPage
            ]
        ]);
    }
}
