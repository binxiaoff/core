<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class LenderEvaluationManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param EntityManager       $entityManager
     * @param TranslatorInterface $translator
     */
    public function __construct(EntityManager $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
    }

    /**
     * @return \lender_questionnaire
     * @throws \Exception
     */
    public function getCurrentQuestionnaire()
    {
        /** @var \lender_questionnaire $questionnaire */
        $questionnaire = $this->entityManager->getRepository('lender_questionnaire');

        if (false === $questionnaire->get(\lender_questionnaire::STATUS_ACTIVE, 'status')) {
            throw new \Exception('Unable to create lender evaluation. No single active questionnaire found');
        }

        return $questionnaire;
    }

    /**
     * @param \lenders_accounts $lender
     * @return \lender_evaluation
     */
    public function getCurrentEvaluation(\lenders_accounts $lender)
    {
        /** @var \lender_evaluation $evaluation */
        $evaluation    = $this->entityManager->getRepository('lender_evaluation');
        $questionnaire = $this->getCurrentQuestionnaire();

        if ($evaluation->get($lender->id_lender_account, '(expiry_date = "0000-00-00" OR expiry_date > NOW()) AND id_lender_questionnaire = ' . $questionnaire->id_lender_questionnaire . ' AND id_lender')) {
            return $evaluation;
        }

        return $this->createEvaluation($lender);
    }

    /**
     * @param \lenders_accounts $lender
     * @return \lender_evaluation
     */
    public function createEvaluation(\lenders_accounts $lender)
    {
        /** @var \lender_evaluation $evaluation */
        $evaluation = $this->entityManager->getRepository('lender_evaluation');
        $evaluation->id_lender_questionnaire = $this->getCurrentQuestionnaire()->id_lender_questionnaire;
        $evaluation->id_lender               = $lender->id_lender_account;
        $evaluation->create();

        return $evaluation;
    }

    /**
     * @param \lenders_accounts $lender
     */
    public function endCurrentEvaluation(\lenders_accounts $lender)
    {
        $evaluation = $this->getCurrentEvaluation($lender);

        if ($evaluation instanceof \lender_evaluation) {
            $evaluation->expiry_date = date('Y-m-d H:i:s');
            $evaluation->update();
        }
    }

    /**
     * @param \lenders_accounts $lender
     * @return \lender_questionnaire_question|null
     */
    public function getLastQuestion(\lenders_accounts $lender)
    {
        $evaluation = $this->getCurrentEvaluation($lender);

        /** @var \lender_questionnaire_question $questionEntity */
        $questionEntity = $this->entityManager->getRepository('lender_questionnaire_question');
        $questions      = $questionEntity->select('id_lender_questionnaire = ' . $evaluation->id_lender_questionnaire, '`order` ASC');

        /** @var \lender_evaluation_answer $answerEntity */
        $answerEntity = $this->entityManager->getRepository('lender_evaluation_answer');
        $answers      = $answerEntity->select('id_lender_evaluation = ' . $evaluation->id_lender_evaluation, 'added ASC, id_lender_evaluation_answer ASC');
        $answersKeys  = array_column($answers, 'id_lender_evaluation_answer', 'id_lender_questionnaire_question');

        foreach ($questions as $question) {
            if (isset($answersKeys[$question['id_lender_questionnaire_question']])) {
                $answerId = $answersKeys[$question['id_lender_questionnaire_question']];

                if (empty($answers[$answerId]['first_answer'])) {
                    $questionEntity->get($question['id_lender_questionnaire_question']);
                    return $questionEntity;
                } elseif (
                    $question['type'] === \lender_questionnaire_question::TYPE_BOOLEAN
                    && \lender_questionnaire_question::VALUE_BOOLEAN_FALSE === $answers[$answerId]['first_answer']
                    && empty($answers[$answerId]['second_answer'])
                ) {
                    $questionEntity->get($question['id_lender_questionnaire_question']);
                    return $questionEntity;
                }
            } else {
                $questionEntity->get($question['id_lender_questionnaire_question']);
                return $questionEntity;
            }
        }

        return null;
    }

    /**
     * @param \lender_evaluation_answer $answer
     * @return \lender_questionnaire_question|null
     */
    public function getNextQuestion(\lender_evaluation_answer $answer)
    {
        $lastQuestionOrder = null;

        /** @var \lender_evaluation $evaluation */
        $evaluation = $this->entityManager->getRepository('lender_evaluation');
        $evaluation->get($answer->id_lender_evaluation);

        /** @var \lender_questionnaire_question $questionEntity */
        $questionEntity = $this->entityManager->getRepository('lender_questionnaire_question');
        $questions      = $questionEntity->select('id_lender_questionnaire = ' . $evaluation->id_lender_questionnaire, '`order` ASC');

        foreach ($questions as $question) {
            // Question before answer
            if (null === $lastQuestionOrder && $question['id_lender_questionnaire_question'] != $answer->id_lender_questionnaire_question) {
                continue;
            // Question that corresponds to answer
            } elseif ($question['id_lender_questionnaire_question'] == $answer->id_lender_questionnaire_question) {
                $lastQuestionOrder = $question['order'];
            // If question corresponds to estate and is above threshold, skip next question
            } elseif (
                \lender_questionnaire_question::TYPE_ESTATE == $question['type']
                && $answer->first_answer >= \lender_questionnaire_question::VALUE_ESTATE_THRESHOLD
            ) {
                continue;
            } else {
                $questionEntity->get($question['id_lender_questionnaire_question']);
                return $questionEntity;
            }
        }

        return null;
    }

    /**
     * @param \lender_questionnaire_question $question
     * @param \lender_evaluation             $evaluation
     * @param string                         $value
     * @return \lender_evaluation_answer
     */
    public function saveAnswer(\lender_questionnaire_question $question, \lender_evaluation $evaluation, $value = '')
    {
        /** @var \lender_evaluation_answer $answer */
        $answer = $this->entityManager->getRepository('lender_evaluation_answer');

        if ($answer->get($evaluation->id_lender_evaluation, 'id_lender_questionnaire_question = ' . $question->id_lender_questionnaire_question . ' AND id_lender_evaluation')) {
            // Do not switch first answer and second answer assignment
            $answer->second_answer = empty($answer->first_answer) ? '' : $value;
            $answer->first_answer  = empty($answer->first_answer) ? $value : $answer->first_answer;
            $answer->update();
        } else {
            $answer->id_lender_questionnaire_question = $question->id_lender_questionnaire_question;
            $answer->id_lender_evaluation             = $evaluation->id_lender_evaluation;
            $answer->second_answer                    = empty($answer->first_answer) ? '' : $value; // Do not switch first answer and second answer assignment
            $answer->first_answer                     = empty($answer->first_answer) ? $value : $answer->first_answer;
            $answer->create();
        }

        return $answer;
    }

    /**
     * @param \lenders_accounts $lender
     * @return string[]|null
     */
    public function getAdvices(\lenders_accounts $lender)
    {
        $advices       = [];
        $evaluation    = $this->getCurrentEvaluation($lender);
        $questionTypes = [\lender_questionnaire_question::TYPE_ESTATE, \lender_questionnaire_question::TYPE_MONTHLY_SAVINGS, \lender_questionnaire_question::TYPE_BLOCKING_PERIOD];

        /** @var \lender_questionnaire_question $questionEntity */
        $questionEntity = $this->entityManager->getRepository('lender_questionnaire_question');
        $questions      = $questionEntity->select('id_lender_questionnaire = ' . $evaluation->id_lender_questionnaire . ' AND type IN (' . implode(',', $questionTypes) . ')', '`order` ASC');
        $questions      = array_column($questions, 'id_lender_questionnaire_question', 'type');

        /** @var \lender_evaluation_answer $answerEntity */
        $answerEntity = $this->entityManager->getRepository('lender_evaluation_answer');
        $answers      = $answerEntity->select('id_lender_evaluation = ' . $evaluation->id_lender_evaluation . ' AND id_lender_questionnaire_question IN (' . implode(',', $questions) . ')');
        $answers      = array_column($answers, 'first_answer', 'id_lender_questionnaire_question');

        if (in_array('', $answers)) {
            return null;
        }

        $estate         = $answers[$questions[\lender_questionnaire_question::TYPE_ESTATE]];
        $monthlySavings = $answers[$questions[\lender_questionnaire_question::TYPE_ESTATE]];

        if ($estate < \lender_questionnaire_question::VALUE_ESTATE_THRESHOLD && $monthlySavings < \lender_questionnaire_question::VALUE_MONTHLY_SAVINGS_THRESHOLD) {
            $advices[] = $this->translator->trans('lender-evaluation_low-estate-low-savings-advice');
            return $advices;
        }

        if ($estate >= \lender_questionnaire_question::VALUE_ESTATE_THRESHOLD) {
            $advices[] = str_replace(
                ['%maximumAmount%', '%maximumAmount100%', '%maximumAmount200%'],
                [floor($estate / 10), floor($estate / 1000), floor($estate / 2000)],
                $this->translator->trans('lender-evaluation_low-estate-advice')
            );
        } elseif ($estate < \lender_questionnaire_question::VALUE_ESTATE_THRESHOLD && $monthlySavings >= \lender_questionnaire_question::VALUE_MONTHLY_SAVINGS_THRESHOLD) {
            $advices[] = str_replace(
                ['%maximumAmount%'],
                [floor($estate / 20) * 20],
                $this->translator->trans('lender-evaluation_low-savings-advice')
            );
        }

        $blockingPeriod = $answers[$questions[\lender_questionnaire_question::TYPE_BLOCKING_PERIOD]];

        if ($blockingPeriod <= \lender_questionnaire_question::VALUE_BLOCKING_PERIOD_THRESHOLD_1) {
            $advices[] = $this->translator->trans('lender-evaluation_blocking-period-less-1-year-advice');
        } elseif ($blockingPeriod <= \lender_questionnaire_question::VALUE_BLOCKING_PERIOD_THRESHOLD_2) {
            $advices[] = $this->translator->trans('lender-evaluation_blocking-period-less-3-years-advice');
        } elseif ($blockingPeriod <= \lender_questionnaire_question::VALUE_BLOCKING_PERIOD_THRESHOLD_3) {
            $advices[] = $this->translator->trans('lender-evaluation_blocking-period-less-5-years-advice');
        } else {
            $advices[] = $this->translator->trans('lender-evaluation_blocking-period-more-5-years-advice');
        }

        return $advices;
    }

    /**
     * @param \lender_evaluation $evaluation
     * @param string             $event
     * @param string             $message
     * @return \lender_evaluation_log
     */
    public function saveAdvice(\lender_evaluation $evaluation, $event, $message)
    {
        /** @var \lender_evaluation_log $advice */
        $advice = $this->entityManager->getRepository('lender_evaluation_log');
        $advice->id_lender_evaluation = $evaluation->id_lender_evaluation;
        $advice->event                = $event;
        $advice->message              = $message;
        $advice->create();

        return $advice;
    }
}
