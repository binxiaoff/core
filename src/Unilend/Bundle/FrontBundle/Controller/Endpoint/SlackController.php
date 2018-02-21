<?php

namespace Unilend\Bundle\FrontBundle\Controller\Endpoint;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;

class SlackController extends Controller
{
    /**
     * @Route("/ws/slack/command", name="slack_command_api_endpoint")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function commandAction(Request $request)
    {
        if (
            $request->request->getAlnum('token') !== $this->getParameter('slack.command_token')
            || $request->request->getAlnum('team_id') !== $this->getParameter('slack.team_id')
        ) {
            throw new NotFoundHttpException();
        }

        $message = $request->request->get('text', '');

        if ('help' === substr(trim($message), 0, 4)) {
            return new JsonResponse([
                'response_type' => 'ephemeral',
                'text'          => 'Entrez un numéro de SIREN pour connaître l\'éligibilité d\'une entreprise (ex : `/unilend 790766034`)'
            ]);
        }

        if (1 === preg_match('/^[^0-9]*([0-9]{9})[^0-9]*$/', $message, $matches)) {
            $siren = $matches[1];
            return $this->sirenCheckerCommand($siren, $request);
        }

        return new JsonResponse([
            'response_type' => 'ephemeral',
            'text'          => 'J\'aime bien discuter cher @' . $request->request->get('user_name') . ' mais je ne vais pas pouvoir vous aider'
        ]);
    }

    /**
     * @param string  $siren
     * @param Request $request
     *
     * @return JsonResponse
     */
    private function sirenCheckerCommand($siren, Request $request)
    {
        if (empty($request->request->get('user_name')) || empty($request->request->get('response_url'))) {
            return new JsonResponse([
                'response_type' => 'ephemeral',
                'text'          => 'Requête invalide'
            ]);
        }
        $userManager = $this->get('unilend.service.back_office_user_manager');

        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $userRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Users');
        $user           = $userRepository->findOneBy([
            'slack'  => $request->request->get('user_name'),
            'status' => Users::STATUS_ONLINE
        ]);

        if (null === $user || false === ($userManager->isGrantedSales($user) || $userManager->isGrantedManagement($user))) {
            return new JsonResponse([
                'response_type' => 'ephemeral',
                'text'          => 'Vous ne disposez pas des droits nécessaires. Veuillez contacter l\'administrateur pour en savoir plus.'
            ]);
        }

        $riskCheck = $this->get('unilend.service.eligibility.eligibility_manager')->checkSirenEligibility($siren);

        $eligibility = 'Éligible';
        $color       = 'good';
        $fields      = [[
            'title' => 'SIREN',
            'value' => $siren,
            'short' => true
        ]];

        if (empty($riskCheck) || $riskCheck[0] !== ProjectsStatus::NON_ELIGIBLE_REASON_UNKNOWN_SIREN) {
            try {
                $companyIdentity = $this->get('unilend.service.ws_client.altares_manager')->getCompanyIdentity($siren);

                if (false === empty($companyIdentity->getCorporateName())) {
                    $fields[] = [
                        'title' => 'Nom société',
                        'value' => $companyIdentity->getCorporateName(),
                        'short' => true
                    ];
                }
            } catch (\Exception $exception) {
                unset($exception);
            }
        }

        if (is_array($riskCheck) && false === empty($riskCheck)) {
            if (ProjectsStatus::UNEXPECTED_RESPONSE === substr($riskCheck[0], 0, strlen(ProjectsStatus::UNEXPECTED_RESPONSE))) {
                $eligibility     = 'Vérification impossible';
                $color           = 'warning';
                $rejectionReason = $this->get('unilend.service.project_status_manager')->getRejectionReasonTranslation($riskCheck[0]);
                $fields[]        = [
                    'title' => 'WS indisponible',
                    'value' => $rejectionReason,
                    'short' => false
                ];
            } else {
                $eligibility     = 'Non éligible';
                $color           = 'danger';
                $rejectionReason = $this->get('unilend.service.project_status_manager')->getRejectionReasonTranslation($riskCheck[0]);
                $fields[]        = [
                    'title' => 'Motif de rejet',
                    'value' => $rejectionReason,
                    'short' => false
                ];
            }
        }

        $client = new Client();
        $client->request('POST', $request->request->get('response_url'), [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode([
                'response_type' => 'in_channel',
                'text'          => '<' . $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_admin') . '/dossiers/add/siren/' . $siren . '|Créer un dossier pour ce SIREN>',
                'unfurl_links'  => false,
                'attachments'   => [[
                    'fallback'   => $eligibility,
                    'title'      => $eligibility,
                    'color'      => $color,
                    'fields'     => $fields
                ]]
            ])
        ]);

        return new JsonResponse([
            'response_type' => 'ephemeral',
            'text'          => ''
        ]);
    }
}
