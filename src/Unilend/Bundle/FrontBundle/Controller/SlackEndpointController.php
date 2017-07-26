<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use GuzzleHttp\Client;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;

class SlackEndpointController extends Controller
{
    /**
     * @Route("/slack-command-api-endpoint", name="slack_command_api_endpoint")
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

        /** @var EntityManager $entityManager */
        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $userRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Users');
        $user           = $userRepository->findOneBy([
            'slack'  => $request->request->get('user_name'),
            'status' => Users::STATUS_ONLINE
        ]);

        if (null === $user || false === in_array($user->getIdUserType()->getIdUserType(), [\users_types::TYPE_ADMIN, \users_types::TYPE_DIRECTION, \users_types::TYPE_COMMERCIAL])) {
            return new JsonResponse([
                'response_type' => 'ephemeral',
                'text'          => 'Vous ne disposez pas des droits nécessaires. Veuillez contacter l\'administrateur pour en savoir plus.'
            ]);
        }

        $riskCheck       = $this->get('unilend.service.eligibility.eligibility_manager')->checkSirenEligibility($siren);
        $companyIdentity = $this->get('unilend.service.ws_client.altares_manager')->getCompanyIdentity($siren);

        $eligibility = 'Éligible';
        $color       = 'good';
        $fields      = [[
            'title' => 'SIREN',
            'value' => $siren,
            'short' => true
        ]];

        if (null !== $companyIdentity && false === empty($companyIdentity->getCorporateName())) {
            $fields[] = [
                'title' => 'Nom société',
                'value' => $companyIdentity->getCorporateName(),
                'short' => true
            ];
        }

        if (is_array($riskCheck) && false === empty($riskCheck)) {
            $eligibility     = 'Non éligible';
            $color           = 'danger';
            $rejectionReason = $this->get('unilend.service.project_status_manager')->getRejectionReasonTranslation($riskCheck[0]);
            $fields[]        = [
                'title' => 'Motif de rejet',
                'value' => $rejectionReason,
                'short' => false
            ];
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
