<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;

class LenderCIPController extends Controller
{
    /**
     * @Route("/conseil-cip", name="cip_index")
     * @Template("lender_cip/index.html.twig")
     *
     * @return array
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/conseil-cip/questionnaire", name="cip_continue_questionnaire")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function questionnaireAction()
    {
        $cipManager    = $this->get('unilend.service.cip_manager');
        $entityManager = $this->get('unilend.service.entity_manager');
        $template      = [
            'total_steps' => 10
        ];

        /** @var UserLender $user */
        $user = $this->getUser();

        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        $lender->get($user->getClientId());

        if (null === $cipManager->getCurrentEvaluation($lender)) {
            $template['current_step'] = 1;
            return $this->render('lender_cip/start.html.twig', $template);
        }

        $question = $cipManager->getLastQuestion($lender);

        if (null === $question) {
            return $this->render('lender_cip/advice.html.twig', $template);
        } else {
            $template['current_step'] = $question->order + 1;
            $template['answers']      = $cipManager->getAnswersByType($lender);
            $template['question']     = [
                'id'   => $question->id_lender_questionnaire_question,
                'type' => $question->type
            ];

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
        $questionId  = $request->request->get('question');
        $answerValue = $request->request->get('answer');

        if (null === $questionId) {
            // @todo error message
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        $entityManager = $this->get('unilend.service.entity_manager');

        if (null === $answerValue) {
            /** @var \lender_questionnaire_question $question */
            $question = $entityManager->getRepository('lender_questionnaire_question');
            $question->get(\lender_questionnaire_question::TYPE_VALUE_OTHER_FINANCIAL_PRODUCTS_USE, 'type');

            if ($questionId != $question->id_lender_questionnaire_question) {
                // @todo error message
                return $this->redirectToRoute('cip_continue_questionnaire');
            }
        }

        $cipManager = $this->get('unilend.service.cip_manager');

        /** @var UserLender $user */
        $user = $this->getUser();

        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        $lender->get($user->getClientId());

        $evaluation = $cipManager->getCurrentEvaluation($lender);

        if (null === $evaluation) {
            // @todo error message
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        $question = $cipManager->getLastQuestion($lender);

        if ($question->id_lender_questionnaire_question != $questionId) {
            // @todo error message
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        $cipManager->saveAnswer($question, $evaluation, $answerValue);

        return $this->redirectToRoute('cip_continue_questionnaire');
    }

    /**
     * @Route("/conseil-cip/commencer", name="cip_start_questionnaire")
     */
    public function startQuestionnaireAction()
    {
        /** @var UserLender $user */
        $user = $this->getUser();

        /** @var \lenders_accounts $lender */
        $lender = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lender->get($user->getClientId());

        $cipManager = $this->get('unilend.service.cip_manager');
        $cipManager->startEvaluation($lender);

        return $this->redirectToRoute('cip_continue_questionnaire');
    }

    /**
     * @Route("/conseil-cip/reset", name="cip_reset_questionnaire")
     * @Template("lender_cip/boolean.html.twig")
     *
     * @return array
     */
    public function resetQuestionnaireAction()
    {
        return [];
    }
}
