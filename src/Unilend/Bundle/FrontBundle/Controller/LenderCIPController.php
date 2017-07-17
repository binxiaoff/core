<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Knp\Snappy\GeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\CIPManager;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;

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
        /** @var Clients $client */
        $client                 = $this->getClient();
        $cipManager             = $this->get('unilend.service.cip_manager');
        $template               = [
            'isCIPActive' => true
        ];
        $evaluation             = $cipManager->getCurrentEvaluation($client);
        $template['clientHash'] = $this->getUser()->getHash();

        if (null === $evaluation) {
            $template['evaluation'] = false;
        } elseif (false === $cipManager->isValidEvaluation($evaluation)) {
            $template['evaluation'] = true;
        } else {
            $template['evaluation'] = true;
            $template['advices']    = $this->getFormatedAdvice($client);
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
        /** @var Clients $client */
        $client     = $this->getClient();
        $cipManager = $this->get('unilend.service.cip_manager');
        $evaluation = $cipManager->getCurrentEvaluation($client);

        if (null !== $evaluation && count($cipManager->getAnswersByType($evaluation)) > 0) {
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        if (null !== $request->query->get('start')) {
            $cipManager->startEvaluation($client);
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        return $this->render('lender_cip/start.html.twig', [
            'isCIPActive'  => true,
            'current_step' => 1,
            'total_steps'  => self::TOTAL_QUESTIONNAIRE_STEPS
        ]);
    }

    /**
     * @Route("/conseil-cip/questionnaire", name="cip_continue_questionnaire")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function questionnaireAction()
    {
        /** @var Clients $client */
        $client     = $this->getClient();
        $cipManager = $this->get('unilend.service.cip_manager');
        $evaluation = $cipManager->getCurrentEvaluation($client);
        $template = [
            'isCIPActive' => true,
            'total_steps' => self::TOTAL_QUESTIONNAIRE_STEPS
        ];

        if (null === $evaluation || false === $cipManager->isEvaluationStarted($evaluation)) {
            return $this->redirectToRoute('cip_start_questionnaire');
        }

        $lastQuestion = $cipManager->getLastQuestion($evaluation);
        $nextQuestion = $cipManager->getNextQuestion($evaluation);
        $answers      = $cipManager->getAnswersByType($evaluation);
        $lastAnswer   = $answers[$lastQuestion->type];

        if (null === $nextQuestion && '' !== $lastAnswer['first_answer']) {
            $advices = $this->getFormatedAdvice($client);

            $cipManager->saveLog($evaluation, \lender_evaluation_log::EVENT_ADVICE, $advices);

            $template['advices']         = $advices;
            $template['validEvaluation'] = $cipManager->isValidEvaluation($evaluation);
            $template['clientHash']      = $this->getUser()->getHash();
            return $this->render('lender_cip/advice.html.twig', $template);
        } else {
            $template['current_step'] = $lastQuestion->order + 1;
            $template['answers']      = $answers;
            $template['question']     = [
                'id'   => $lastQuestion->id_lender_questionnaire_question,
                'type' => $lastQuestion->type,
            ];

            if ('' !== $lastAnswer['first_answer']) {
                $template['answers']['current']['first'] = $lastAnswer['first_answer'];
            }

            if ('' !== $lastAnswer['second_answer']) {
                $template['answers']['current']['second'] = $lastAnswer['second_answer'];
            }

            if ($lastQuestion->isBooleanType($lastQuestion->type)) {
                $template['lenderType'] = $client->isNaturalPerson() ? 'physical' : 'moral';

                if (\lender_questionnaire_question::TYPE_AWARE_DIVIDE_INVESTMENTS === $lastQuestion->type) {
                    $template['question']['valid_answer']   = \lender_questionnaire_question::VALUE_BOOLEAN_FALSE;
                    $template['question']['invalid_answer'] = \lender_questionnaire_question::VALUE_BOOLEAN_TRUE;
                } else {
                    $template['question']['valid_answer']   = \lender_questionnaire_question::VALUE_BOOLEAN_TRUE;
                    $template['question']['invalid_answer'] = \lender_questionnaire_question::VALUE_BOOLEAN_FALSE;
                }
            }

            return $this->render('lender_cip/question-' . $lastQuestion->type . '.html.twig', $template);
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
        /** @var Clients $client */
        $client     = $this->getClient();
        $cipManager = $this->get('unilend.service.cip_manager');
        $evaluation = $cipManager->getCurrentEvaluation($client);

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

        $answerValue   = filter_var($request->request->get('answer'), FILTER_SANITIZE_STRING);
        $continueValue = $request->request->get('continue');

        if (in_array($continueValue, ['next_question', 'loop', 'back'])) {
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
                    $bidInfo = $request->getSession()->getFlashBag()->peek('cipBid');
                    $bid     = false === empty($bidInfo) ? array_shift($bidInfo) : [];

                    if (false === empty($bid['project'])) {
                        /** @var \projects $project */
                        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

                        if (is_numeric($bid['project']) && $project->get($bid['project'])) {
                            return $this->redirectToRoute('project_detail', ['projectSlug' => $project->slug]);
                        }

                        $request->getSession()->getFlashBag()->remove('cipBid');
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
        /** @var Clients $client */
        $client     = $this->getClient();
        $cipManager = $this->get('unilend.service.cip_manager');
        $cipManager->validateLenderEvaluation($client);
        $this->sendAdviceEmail($client);

        $bid = $request->getSession()->getFlashBag()->peek('cipBid');

        if (false === empty($bid[0]['project'])) {
            /** @var \projects $project */
            $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

            if (is_numeric($bid[0]['project']) && $project->get($bid[0]['project'])) {
                return $this->redirectToRoute('project_detail', ['projectSlug' => $project->slug]);
            }

            $request->getSession()->getFlashBag()->remove('cipBid');
        }

        return $this->redirectToRoute('lender_dashboard');
    }

    /**
     * @Route("/conseil-cip/reset", name="cip_reset_questionnaire")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resetQuestionnaireAction()
    {
        $cipManager = $this->get('unilend.service.cip_manager');
        $cipManager->endLenderEvaluation($this->getClient());

        return $this->redirectToRoute('cip_start_questionnaire');
    }

    /**
     * @Route("/pdf/conseil-cip/{clientHash}", name="pdf_cip", requirements={"clientHash": "[0-9a-f-]{32,36}"})
     *
     * @param string $clientHash
     * @return Response
     */
    public function cipAdvisePdfAction($clientHash)
    {
        /** @var CIPManager $cipManager */
        $cipManager = $this->get('unilend.service.cip_manager');
        /** @var GeneratorInterface $snappy */
        $snappy = $this->get('knp_snappy.pdf');
        /** @var Clients $client */
        $client = $this->getClient();

        if ($client->getHash() !== $clientHash) {
            throw new \Exception('ClientHash does not match the client in session');
        }

        $pdfHeader             = $this->renderView('/pdf/cip/header.html.twig');
        $pdfFooter             = $this->renderView('/pdf/cip/footer.html.twig');

        $content['advice']     = $this->getFormatedAdvice($client);
        $content['clientName'] = $client->getPrenom();
        $pdfContent            = $this->renderView('/pdf/cip/advice.html.twig', $content);

        $evaluation            = $cipManager->getCurrentEvaluation($client);
        $evaluationDate        = new \DateTime($evaluation->expiry_date . ' - 1 year');
        $filename              = sprintf('conseils-investissement-%s.pdf', $evaluationDate->format('Y-m-d'));

        return new Response(
            $snappy->getOutputFromHtml(
                $pdfContent, [
                    'footer-html' => $pdfFooter,
                    'header-html' => $pdfHeader,
                    'margin-top'    => 30,
                    'margin-right'  => 20,
                    'margin-bottom' => 20,
                    'margin-left'   => 20
                ]
            ),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename)
            ]
        );
    }

    /**
     * @param Clients $client
     */
    private function sendAdviceEmail(Clients $client)
    {
        /** @var \settings $settings */
        $settings =  $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Facebook', 'type');
        $fbLink = $settings->value;
        $settings->get('Twitter', 'type');
        $twLink = $settings->value;
        $wallet = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        $varMail = [
            'surl'                 => $this->get('assets.packages')->getUrl(''),
            'url'                  => $this->get('assets.packages')->getUrl(''),
            'prenom'               => $client->getPrenom(),
            'email_p'              => $client->getEmail(),
            'advice'               => str_replace('h5', 'h3', $this->getFormatedAdvice($client)),
            'advice_pdf_link'      => $this->generateUrl('pdf_cip', ['clientHash' => $client->getHash()], UrlGeneratorInterface::ABSOLUTE_URL),
            'motif_virement'       => $wallet->getWireTransferPattern(),
            'lien_fb'              => $fbLink,
            'lien_tw'              => $twLink
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-conseil-cip', $varMail);
        $message->setTo($client->getEmail());
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    private function getClient()
    {
        /** @var UserLender $user */
        $user     = $this->getUser();
        $client = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($user->getClientId());

        return $client;
    }

    /**
     * @param Clients $client
     *
     * @return string
     */
    private function getFormatedAdvice(Clients $client)
    {
        /** @var CIPManager $cipManager */
        $cipManager = $this->get('unilend.service.cip_manager');
        $advices    = $cipManager->getAdvices($client);

        if (null === $advices) {
            $advices = [];
        }

        return implode("\n", $advices);
    }
}
