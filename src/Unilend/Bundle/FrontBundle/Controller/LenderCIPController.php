<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Knp\Snappy\GeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\CIPManager;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\Bundle\FrontBundle\Service\ProjectDisplayManager;

class LenderCIPController extends Controller
{
    const TOTAL_QUESTIONNAIRE_STEPS = 10;

    /**
     * @Route("/conseil-cip", name="cip_index")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $cipManager = $this->get('unilend.service.cip_manager');
        $lender     = $this->getLenderAccount();
        $template   = [];

        $evaluation = $cipManager->getCurrentEvaluation($lender);

        $template['clientHash'] = $this->getUser()->getHash();

        if (null === $evaluation) {
            $template['evaluation'] = false;
        } elseif (false === $cipManager->isValidEvaluation($evaluation)) {
            $template['evaluation'] = true;
        } else {
            $template['evaluation'] = true;
            $template['advices']    = implode("\n", $cipManager->getAdvices($lender));
        }

        return $this->render('lender_cip/index.html.twig', $template);
    }

    /**
     * @Route("/conseil-cip/commencer", name="cip_start_questionnaire")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function startQuestionnaireAction(Request $request)
    {
        $cipManager = $this->get('unilend.service.cip_manager');
        $lender     = $this->getLenderAccount();
        $evaluation = $cipManager->getCurrentEvaluation($lender);

        if (null !== $evaluation && count($cipManager->getAnswersByType($evaluation)) > 0) {
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        if (null !== $request->query->get('start')) {
            $cipManager->startEvaluation($lender);
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        return $this->render('lender_cip/start.html.twig', ['current_step' => 1, 'total_steps' => self::TOTAL_QUESTIONNAIRE_STEPS]);
    }

    /**
     * @Route("/conseil-cip/questionnaire", name="cip_continue_questionnaire")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function questionnaireAction()
    {
        $cipManager = $this->get('unilend.service.cip_manager');
        $lender     = $this->getLenderAccount();
        $evaluation = $cipManager->getCurrentEvaluation($lender);
        $template   = [
            'total_steps' => self::TOTAL_QUESTIONNAIRE_STEPS
        ];

        if (null === $evaluation) {
            return $this->redirectToRoute('cip_start_questionnaire');
        }

        $nextQuestion = $cipManager->getNextQuestion($evaluation);

        if (null === $nextQuestion) {
            $advices = implode("\n", $cipManager->getAdvices($lender));

            $cipManager->saveLog($evaluation, \lender_evaluation_log::EVENT_ADVICE, $advices);

            $template['advices']         = $advices;
            $template['validEvaluation'] = $cipManager->isValidEvaluation($evaluation);
            $template['clientHash']      = $this->getUser()->getHash();
            return $this->render('lender_cip/advice.html.twig', $template);
        } else {
            $question = $cipManager->getLastQuestion($evaluation);
            $answers  = $cipManager->getAnswersByType($evaluation);
            $answer   = $answers[$question->type];

            $template['current_step'] = $question->order + 1;
            $template['answers']      = $answers;
            $template['question']     = [
                'id'   => $question->id_lender_questionnaire_question,
                'type' => $question->type,
            ];

            if ('' !== $answer['first_answer']) {
                $template['answers']['current']['first'] = $answer['first_answer'];
            }

            if ('' !== $answer['second_answer']) {
                $template['answers']['current']['second'] = $answer['second_answer'];
            }

            return $this->render('lender_cip/question-' . $question->type . '.html.twig', $template);
        }
    }

    /**
     * @Route("/conseil-cip/questionnaire/form", name="cip_form_questionnaire")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function questionnaireFormAction(Request $request)
    {
        $cipManager    = $this->get('unilend.service.cip_manager');
        $lender        = $this->getLenderAccount();
        $evaluation    = $cipManager->getCurrentEvaluation($lender);

        if (null === $evaluation) {
            // @todo error message
            return $this->redirectToRoute('cip_start_questionnaire');
        }

        if ('values' === $request->query->get('reset')) {
            $cipManager->resetValues($evaluation);
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        $questionId = $request->request->get('question');

        if (null === $questionId) {
            // @todo error message
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        $question = $cipManager->getLastQuestion($evaluation);

        if ($question->id_lender_questionnaire_question != $questionId) {
            // @todo error message
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        $answerValue   = $request->request->get('answer');
        $continueValue = $request->request->get('continue');

        if (null !== $continueValue) {
            switch ($continueValue) {
                case 'next_question':
                    $cipManager->insertQuestion($evaluation, $cipManager->getNextQuestion($evaluation));
                    return $this->redirectToRoute('cip_continue_questionnaire');
                case 'loop':
                    $answer = $cipManager->getAnswer($evaluation, $question);
                    if (null !== $answer) {
                        $answer->status = \lender_evaluation_answer::STATUS_INACTIVE;
                        $answer->update();
                    }
                    $cipManager->insertQuestion($evaluation, $question);
                    return $this->redirectToRoute('cip_continue_questionnaire');
                case 'back':
                    $bid = $request->getSession()->get('cipBid');

                    if (false === empty($bid['project'])) {
                        /** @var \projects $project */
                        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

                        if (is_numeric($bid['project']) && $project->get($bid['project'])) {
                            return $this->redirectToRoute('project_detail', ['projectId' => $project->id_project]);
                        }

                        $request->getSession()->remove('cipBid');
                    }

                    return $this->redirectToRoute('cip_index');
            }
        } else {
            if (null === $answerValue) {
                /** @var \lender_questionnaire_question $questionEntity */
                $questionEntity = $this->get('unilend.service.entity_manager')->getRepository('lender_questionnaire_question');
                $questionEntity->get(\lender_questionnaire_question::TYPE_VALUE_OTHER_FINANCIAL_PRODUCTS_USE, 'type');

                if ($questionId != $questionEntity->id_lender_questionnaire_question) {
                    // @todo error message
                    return $this->redirectToRoute('cip_continue_questionnaire');
                }
            }

            $cipManager->saveAnswer($question, $evaluation, $answerValue);
        }

        return $this->redirectToRoute('cip_continue_questionnaire');
    }

    /**
     * @Route("/conseil-cip/valider", name="cip_validate_questionnaire")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function validateQuestionnaireAction(Request $request)
    {
        $cipManager = $this->get('unilend.service.cip_manager');
        $cipManager->validateLenderEvaluation($this->getLenderAccount());
        $this->sendAdviceEmail($this->getClient());

        $bid = $request->getSession()->get('cipBid');

        if (false === empty($bid['project'])) {
            /** @var \projects $project */
            $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

            if (is_numeric($bid['project']) && $project->get($bid['project'])) {
                return $this->redirectToRoute('place_bid', ['projectId' => $project->id_project]);
            }

            $request->getSession()->remove('cipBid');
        }

        return $this->redirectToRoute('cip_index');
    }

    /**
     * @Route("/conseil-cip/reset", name="cip_reset_questionnaire")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resetQuestionnaireAction()
    {
        $cipManager = $this->get('unilend.service.cip_manager');
        $cipManager->endLenderEvaluation($this->getLenderAccount());

        return $this->redirectToRoute('cip_start_questionnaire');
    }

    /**
     * @Route("/conseil-cip/bid", name="cip_bid")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bidAction(Request $request)
    {
        $entityManager = $this->get('unilend.service.entity_manager');
        $cipManager    = $this->get('unilend.service.cip_manager');

        $rate   = $request->query->get('rate');
        $amount = $request->query->get('amount');

        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');

        if (
            false === $project->get($request->query->get('project'), 'slug')
            || empty($rate)
            || empty($amount)
        ) {
            return new JsonResponse([
                'error'   => true,
                'message' => 'Invalid parameters'
            ]);
        }

        $request->getSession()->set('cipBid', ['amount' => $amount, 'rate' => $rate, 'project' => $project->id_project]);

        $response   = [];
        $lender     = $this->getLenderAccount();
        $evaluation = $cipManager->getCurrentEvaluation($lender);

        /** @var \bids $bid */
        $bid                    = $entityManager->getRepository('bids');
        $bid->id_lender_account = $lender->id_lender_account;
        $bid->id_project        = $project->id_project;
        $bid->amount            = $amount * 100;
        $bid->rate              = $rate;

        $validationNeeded = $cipManager->isCIPValidationNeeded($bid);
        $response['validation']  = $validationNeeded;

        if ($validationNeeded && null !== $evaluation && $cipManager->isValidEvaluation($evaluation)) {
            $advices    = [];
            $indicators = $cipManager->getIndicators($lender);

            if (null !== $indicators[CIPManager::INDICATOR_TOTAL_AMOUNT]) {
                /** @var \bids $bids */
                $bids        = $entityManager->getRepository('bids');
                $totalAmount = $bids->sum('id_lender_account = ' . $lender->id_lender_account . ' AND status IN (' . \bids::STATUS_BID_PENDING . ', ' . \bids::STATUS_BID_ACCEPTED . ', ' . \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY . ')', 'ROUND(amount / 100)');

                if ($totalAmount > $indicators[CIPManager::INDICATOR_TOTAL_AMOUNT]) {
                    $advices[CIPManager::INDICATOR_TOTAL_AMOUNT] = true;
                }
            }

            if (null !== $indicators[CIPManager::INDICATOR_AMOUNT_BY_MONTH]) {
                /** @var \bids $bids */
                $bids        = $entityManager->getRepository('bids');
                $totalAmount = $bids->sum('id_lender_account = ' . $lender->id_lender_account . ' AND added >= DATE_SUB(NOW(), INTERVAL 1 MONTH) AND status IN (' . \bids::STATUS_BID_PENDING . ', ' . \bids::STATUS_BID_ACCEPTED . ', ' . \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY . ')', 'ROUND(amount / 100)');

                if ($totalAmount > $indicators[CIPManager::INDICATOR_AMOUNT_BY_MONTH]) {
                    $advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH] = true;
                }
            }

            if (null !== $indicators[CIPManager::INDICATOR_PROJECT_DURATION] && $project->period > $indicators[CIPManager::INDICATOR_PROJECT_DURATION]) {
                $advices[CIPManager::INDICATOR_PROJECT_DURATION] = true;
            }

            if (false === empty($advices)) {
                $message = '';

                if (isset($advices[CIPManager::INDICATOR_TOTAL_AMOUNT], $advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH], $advices[CIPManager::INDICATOR_PROJECT_DURATION])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement total, dépassement seuil mensuel, dépassement durée projet.';
                } elseif (isset($advices[CIPManager::INDICATOR_TOTAL_AMOUNT], $advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement total, dépassement seuil mensuel.';
                } elseif (isset($advices[CIPManager::INDICATOR_TOTAL_AMOUNT], $advices[CIPManager::INDICATOR_PROJECT_DURATION])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement total, dépassement durée projet.';
                } elseif (isset($advices[CIPManager::INDICATOR_TOTAL_AMOUNT])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement total.';
                } elseif (isset($advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH], $advices[CIPManager::INDICATOR_PROJECT_DURATION])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement total, dépassement durée projet.';
                } elseif (isset($advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement seuil mensuel.';
                } elseif (isset($advices[CIPManager::INDICATOR_PROJECT_DURATION])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement durée projet.';
                }

                $cipManager->saveLog($evaluation, \lender_evaluation_log::EVENT_BID_ADVICE, strip_tags($message));
                $response['advices'] = $message;
            }
        } elseif ($validationNeeded) {
            $cipManager->saveLog($evaluation, \lender_evaluation_log::EVENT_BID_EVALUATION_NEEDED);
            $response['questionnaire'] = true;
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/pdf/conseil-cip/{clientHash}", name="pdf_cip")
     */
    public function cipAdvisePdfAction($clientHash)
    {
        /** @var CIPManager $cipManager */
        $cipManager = $this->get('unilend.service.cip_manager');
        /** @var GeneratorInterface $snappy */
        $snappy = $this->get('knp_snappy.pdf');

        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($clientHash, 'hash');

        /** @var \lenders_accounts $lender */
        $lender = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lender->get($client->id_client, 'id_client_owner');

        $content['advice'] = implode("\n", $cipManager->getAdvices($lender));
        $content['information'] = '';

        $evaluation     = $cipManager->getCurrentEvaluation($lender);
        $evaluationData = new \DateTime($evaluation->expiry_date . ' - 1 year');

        $filename = sprintf('conseils-investissement-%s.pdf', $evaluationData->format('Y-m-d'));

        return new Response(
            $snappy->getOutputFromHtml($this->renderView('/pdf/cip_advice.html.twig', $content)),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename)
            ]
        );
    }

    private function sendAdviceEmail(\clients $client)
    {
        /** @var \settings $settings */
        $settings =  $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Facebook', 'type');
        $fbLink = $settings->value;
        $settings->get('Twitter', 'type');
        $twLink = $settings->value;

        /** @var TranslatorInterface */
        $translator = $this->get('translator.default');

        /** @var CIPManager $cipManager */
        $cipManager = $this->get('unilend.service.cip_manager');

        $lender = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lender->get($client->id_client, 'id_client_owner');

        /** @var ProjectDisplayManager $lenderDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        $projects = $projectDisplayManager->getCipAdvisedProjectList($lender);

        $projectListHtml = '';
        if (false === empty($projects)) {
            $projectListHtml .= '<ul>';
            foreach ($projects as $project) {
                $projectListHtml .= '<li>                                 
                                        <a href="' . $this->generateUrl('project_detail', ['projectSlug' => $project['slug']], UrlGenerator::ABSOLUTE_URL) . '"><span style=\'color:#b20066;\'>' . $project['title'] . '</span></a>' .
                    '</li>';
            }
            $projectListHtml .= '</ul>';
        } else {
            $projectListHtml .= $translator->trans('lender-valiation_confirmation-email-no-advised-projects');
        }

        $varMail = array(
            'surl'                 => $this->get('assets.packages')->getUrl(''),
            'url'                  => $this->get('assets.packages')->getUrl(''),
            'prenom'               => $client->prenom,
            'email_p'              => $client->email,
            'advice'               => implode("\n", $cipManager->getAdvices($lender)),
            'advice_pdf_link'      => $this->generateUrl('pdf_cip', ['clientHash' => $client->hash]),
            'advised_project_list' => $projectListHtml,
            'motif_virement'       => $client->getLenderPattern($client->id_client),
            'lien_fb'              => $fbLink,
            'lien_tw'              => $twLink
        );

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-conseil-cip', $varMail);
        $message->setTo($client->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    private function getClient()
    {
        /** @var UserLender $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($clientId);

        return $client;
    }

    /**
     * @return \lenders_accounts
     */
    private function getLenderAccount()
    {
        /** @var UserLender $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();

        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lenderAccount->get($clientId, 'id_client_owner');

        return $lenderAccount;
    }
}
