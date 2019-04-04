<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, Wallet, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\CIPManager;
use Unilend\SwiftMailer\TemplateMessage;

class LenderCIPController extends Controller
{
    const TOTAL_QUESTIONNAIRE_STEPS = 9;

    /**
     * @Route("/conseil-cip", name="cip_index")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function indexAction(?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $cipManager = $this->get('unilend.service.cip_manager');
        $evaluation = $cipManager->getCurrentEvaluation($client);

        $template = [
            'isCIPActive' => true,
            'clientHash'  => $client->getHash()
        ];

        if (null === $evaluation) {
            $template['evaluation'] = false;
        } elseif (false === $cipManager->isValidEvaluation($evaluation)) {
            $template['evaluation'] = true;
        } else {
            $template['evaluation'] = true;
            $template['advices']    = $this->getFormattedAdvice($client);
        }

        return $this->render('lender_cip/index.html.twig', $template);
    }

    /**
     * @Route("/conseil-cip/questionnaire", name="cip_continue_questionnaire")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function questionnaireAction(?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $cipManager = $this->get('unilend.service.cip_manager');
        $evaluation = $cipManager->getCurrentEvaluation($client);
        $template = [
            'isCIPActive' => true,
            'total_steps' => self::TOTAL_QUESTIONNAIRE_STEPS
        ];

        if (null === $evaluation || false === $cipManager->isEvaluationStarted($evaluation)) {
            $evaluation = $cipManager->startEvaluation($client);
        }

        $lastQuestion = $cipManager->getLastQuestion($evaluation);
        $nextQuestion = $cipManager->getNextQuestion($evaluation);
        $answers      = $cipManager->getAnswersByType($evaluation);
        $lastAnswer   = $answers[$lastQuestion->type];

        if (null === $nextQuestion && '' !== $lastAnswer['first_answer']) {
            $advices = $this->getFormattedAdvice($client);

            $cipManager->saveLog($evaluation, \lender_evaluation_log::EVENT_ADVICE, $advices);

            $template['advices']         = $advices;
            $template['validEvaluation'] = $cipManager->isValidEvaluation($evaluation);
            $template['clientHash']      = $client->getHash();
            return $this->render('lender_cip/advice.html.twig', $template);
        } else {
            $template['current_step'] = $lastQuestion->order;
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
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function questionnaireFormAction(Request $request, ?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $cipManager = $this->get('unilend.service.cip_manager');
        $evaluation = $cipManager->getCurrentEvaluation($client);

        if (null === $evaluation) {
            // @todo error message
            return $this->redirectToRoute('cip_continue_questionnaire');
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
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function validateQuestionnaireAction(Request $request, ?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

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
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function resetQuestionnaireAction(?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $cipManager = $this->get('unilend.service.cip_manager');
        $cipManager->endLenderEvaluation($client);

        return $this->redirectToRoute('cip_continue_questionnaire');
    }

    /**
     * @Route("/pdf/conseil-cip/{clientHash}", name="pdf_cip", requirements={"clientHash": "[0-9a-f-]{32,36}"})
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param string $clientHash
     *
     * @return Response
     */
    public function cipAdvisePdfAction(string $clientHash): Response
    {
        /** @var Clients $client */
        $client = $this->get('doctrine.orm.entity_manager')->getRepository(Clients::class)->findOneBy(['hash' => $clientHash]);

        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $cipManager = $this->get('unilend.service.cip_manager');
        $snappy     = $this->get('knp_snappy.pdf');

        $pdfFooter             = $this->renderView('/pdf/cip/footer.html.twig');
        $content['advice']     = $this->getFormattedAdvice($client);
        $content['clientName'] = $client->getPrenom();
        $pdfContent            = $this->renderView('/pdf/cip/advice.html.twig', $content);

        $evaluation            = $cipManager->getCurrentEvaluation($client);
        $evaluationDate        = new \DateTime($evaluation->validated);
        $filename              = sprintf('conseils-investissement-%s.pdf', $evaluationDate->format('Y-m-d'));

        return new Response(
            $snappy->getOutputFromHtml(
                $pdfContent, [
                    'footer-html' => $pdfFooter,
                    'margin-right'  => 20,
                    'margin-bottom' => 20,
                    'margin-left'   => 20
                ]
            ),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename)
            ]
        );
    }

    /**
     * @param Clients $client
     */
    private function sendAdviceEmail(Clients $client): void
    {
        /** @var Wallet $wallet */
        $wallet   = $this->get('doctrine.orm.entity_manager')->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);
        $keywords = [
            'firstName'     => $client->getPrenom(),
            'advice'        => str_replace('h5', 'p', $this->getFormattedAdvice($client)),
            'advicePdfLink' => $this->generateUrl('pdf_cip', ['clientHash' => $client->getHash()], UrlGeneratorInterface::ABSOLUTE_URL),
            'lenderPattern' => $wallet->getWireTransferPattern()
        ];

        /** @var TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-conseil-cip', $keywords);

        try {
            $message->setTo($client->getEmail());
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->warning(
                'Could not send email: preteur-conseil-cip - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param Clients $client
     *
     * @return string
     */
    private function getFormattedAdvice(Clients $client): string
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
