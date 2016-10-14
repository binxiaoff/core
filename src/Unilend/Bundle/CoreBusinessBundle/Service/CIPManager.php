<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

class CIPManager
{
    const INDICATOR_TOTAL_AMOUNT     = 'total_amount';
    const INDICATOR_AMOUNT_BY_MONTH  = 'amount_by_month';
    const INDICATOR_PROJECT_DURATION = 'project_duration';

    /** @var ProductManager */
    private $productManager;

    /** @var ContractAttributeManager */
    private $contractAttributeManager;

    /** @var EntityManager */
    private $entityManager;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param ProductManager           $productManager
     * @param ContractAttributeManager $contractAttributeManager
     * @param EntityManager            $entityManager
     * @param TranslatorInterface      $translator
     */
    public function __construct(
        ProductManager $productManager,
        ContractAttributeManager $contractAttributeManager,
        EntityManager $entityManager,
        TranslatorInterface $translator
    )
    {
        $this->productManager           = $productManager;
        $this->contractAttributeManager = $contractAttributeManager;
        $this->entityManager            = $entityManager;
        $this->translator               = $translator;
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
     * @return \lender_evaluation|null
     */
    public function getCurrentEvaluation(\lenders_accounts $lender)
    {
        /** @var \lender_evaluation $evaluation */
        $evaluation    = $this->entityManager->getRepository('lender_evaluation');
        $questionnaire = $this->getCurrentQuestionnaire();

        if ($evaluation->get($lender->id_lender_account, '(expiry_date = "0000-00-00" OR expiry_date > NOW()) AND id_lender_questionnaire = ' . $questionnaire->id_lender_questionnaire . ' AND id_lender')) {
            return $evaluation;
        }

        return null;
    }

    /**
     * @param \lender_evaluation $evaluation
     * @return bool
     */
    public function isValidEvaluation(\lender_evaluation $evaluation)
    {
        return ($evaluation->expiry_date !== '0000-00-00 00:00:00');
    }

    /**
     * @param \lenders_accounts $lender
     */
    public function startEvaluation(\lenders_accounts $lender)
    {
        if (null === $this->getCurrentEvaluation($lender)) {
            /** @var \lender_evaluation $evaluation */
            $evaluation = $this->entityManager->getRepository('lender_evaluation');
            $evaluation->id_lender_questionnaire = $this->getCurrentQuestionnaire()->id_lender_questionnaire;
            $evaluation->id_lender               = $lender->id_lender_account;
            $evaluation->create();
        }
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
     */
    public function validateEvaluation(\lenders_accounts $lender)
    {
        $expiryDate = new \DateTime('NOW + 1 YEAR');
        $evaluation = $this->getCurrentEvaluation($lender);
        $evaluation->expiry_date = $expiryDate->format('Y-m-d H:i:s');
        $evaluation->update();
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
        $answerEntity   = $this->entityManager->getRepository('lender_evaluation_answer');
        $answers        = $answerEntity->select('id_lender_evaluation = ' . $evaluation->id_lender_evaluation, 'added ASC, id_lender_evaluation_answer ASC');
        $indexedAnswers = [];

        foreach ($answers as $index => $answer) {
            $indexedAnswers[$answer['id_lender_questionnaire_question']] = $answer;
        }

        foreach ($questions as $index => $question) {
            if (isset($indexedAnswers[$question['id_lender_questionnaire_question']])) {
                $answer = $indexedAnswers[$question['id_lender_questionnaire_question']];

                if ('' === $answer['first_answer']) {
                    $questionEntity->get($question['id_lender_questionnaire_question']);
                    return $questionEntity;
                } elseif (
                    in_array($question['type'], [\lender_questionnaire_question::TYPE_AWARE_MONEY_LOSS, \lender_questionnaire_question::TYPE_AWARE_PROGRESSIVE_CAPITAL_REPAYMENT, \lender_questionnaire_question::TYPE_AWARE_RISK_RETURN, \lender_questionnaire_question::TYPE_AWARE_DIVIDE_INVESTMENTS])
                    && \lender_questionnaire_question::VALUE_BOOLEAN_FALSE === $answer['first_answer']
                    && '' === $answer['second_answer']
                ) {
                    $questionEntity->get($question['id_lender_questionnaire_question']);
                    return $questionEntity;
                }
            } else {
                if (\lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS === $question['type']) {
                    $estateAnswer = $indexedAnswers[$questions[$index - 1]['id_lender_questionnaire_question']]['first_answer'];

                    if ($estateAnswer >= \lender_questionnaire_question::VALUE_ESTATE_THRESHOLD) {
                        continue;
                    }
                }
                $questionEntity->get($question['id_lender_questionnaire_question']);

                $answerEntity->id_lender_evaluation             = $evaluation->id_lender_evaluation;
                $answerEntity->id_lender_questionnaire_question = $questionEntity->id_lender_questionnaire_question;
                $answerEntity->create();

                return $questionEntity;
            }
        }

        return null;
    }

    /**
     * @param \lenders_accounts              $lender
     * @param \lender_questionnaire_question $question
     * @return \lender_evaluation_answer|null
     */
    public function getAnswer(\lenders_accounts $lender, \lender_questionnaire_question $question)
    {
        /** @var \lender_evaluation_answer $answer */
        $answer     = $this->entityManager->getRepository('lender_evaluation_answer');
        $evaluation = $this->getCurrentEvaluation($lender);

        if (false === $answer->get($evaluation->id_lender_evaluation, 'id_lender_questionnaire_question = ' . $question->id_lender_questionnaire_question . ' AND id_lender_evaluation')) {
            return null;
        }

        return $answer;
    }

    /**
     * @param \lenders_accounts       $lender
     * @param \lender_evaluation|null $evaluation
     * @return array
     */
    public function getAnswers(\lenders_accounts $lender, \lender_evaluation $evaluation = null)
    {
        /** @var \lender_evaluation_answer $answer */
        $answer = $this->entityManager->getRepository('lender_evaluation_answer');

        if (null === $evaluation || $lender->id_lender_account != $evaluation->id_lender) {
            $evaluation = $this->getCurrentEvaluation($lender);
        }

        return $answer->select('id_lender_evaluation = ' . $evaluation->id_lender_evaluation, 'added ASC, id_lender_evaluation_answer ASC');
    }

    /**
     * @param \lenders_accounts $lender
     * @return array
     */
    public function getAnswersByType(\lenders_accounts $lender)
    {
        /** @var \lender_questionnaire_question $questionEntity */
        $questionEntity   = $this->entityManager->getRepository('lender_questionnaire_question');
        $evaluation       = $this->getCurrentEvaluation($lender);
        $questions        = $questionEntity->select('id_lender_questionnaire = ' . $evaluation->id_lender_questionnaire, '`order` ASC');
        $indexedQuestions = [];
        $indexedAnswers   = [];

        foreach ($questions as $question) {
            $indexedQuestions[$question['id_lender_questionnaire_question']] = $question;
        }

        $answers = $this->getAnswers($lender, $evaluation);

        foreach ($answers as $answer) {
            $indexedAnswers[$indexedQuestions[$answer['id_lender_questionnaire_question']]['type']] = $answer;
        }

        return $indexedAnswers;
    }

    /**
     * @param \lender_questionnaire_question $question
     * @param \lender_evaluation             $evaluation
     * @param string                         $value
     * @return \lender_evaluation_answer|null
     */
    public function saveAnswer(\lender_questionnaire_question $question, \lender_evaluation $evaluation, $value = '')
    {
        /** @var \lender_evaluation_answer $answer */
        $answer = $this->entityManager->getRepository('lender_evaluation_answer');

        if (\lender_questionnaire_question::TYPE_VALUE_OTHER_FINANCIAL_PRODUCTS_USE == $question->type) {
            $value = json_encode(empty($value) ? [] : $value);
        }

        if ($answer->get($evaluation->id_lender_evaluation, 'id_lender_questionnaire_question = ' . $question->id_lender_questionnaire_question . ' AND id_lender_evaluation')) {
            // Do not switch first answer and second answer assignment
            $answer->second_answer = empty($answer->first_answer) ? '' : $value;
            $answer->first_answer  = empty($answer->first_answer) ? $value : $answer->first_answer;

            if (false === $this->isValidAnswer($answer, $question)) {
                return null;
            }

            $answer->update();
        } else {
            $answer->id_lender_questionnaire_question = $question->id_lender_questionnaire_question;
            $answer->id_lender_evaluation             = $evaluation->id_lender_evaluation;
            $answer->first_answer                     = $value;

            if ($this->isValidAnswer($answer, $question)) {
                return null;
            }

            $answer->create();
        }

        return $answer;
    }

    /**
     * @param \lender_evaluation_answer           $answer
     * @param \lender_questionnaire_question|null $question
     * @return bool
     */
    public function isValidAnswer(\lender_evaluation_answer $answer, \lender_questionnaire_question $question = null)
    {
        if (null === $question || $answer->id_lender_questionnaire_question != $question->id_lender_questionnaire_question) {
            /** @var \lender_questionnaire_question $question */
            $question = $this->entityManager->getRepository('lender_questionnaire_question');
            $question->get($answer->id_lender_questionnaire_question);
        }

        switch ($question->type) {
            case \lender_questionnaire_question::TYPE_AWARE_MONEY_LOSS:
            case \lender_questionnaire_question::TYPE_AWARE_PROGRESSIVE_CAPITAL_REPAYMENT:
            case \lender_questionnaire_question::TYPE_AWARE_RISK_RETURN:
            case \lender_questionnaire_question::TYPE_AWARE_DIVIDE_INVESTMENTS:
                return (
                    \lender_questionnaire_question::VALUE_BOOLEAN_TRUE === $answer->first_answer
                    || \lender_questionnaire_question::VALUE_BOOLEAN_TRUE === $answer->second_answer
                );
            case \lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE:
            case \lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS:
            case \lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD:
            case \lender_questionnaire_question::TYPE_VALUE_OTHER_FINANCIAL_PRODUCTS_USE:
                return ('' !== $answer->first_answer);
        }

        return false;
    }

    /**
     * @param \lenders_accounts $lender
     * @return string[]|null
     */
    public function getAdvices(\lenders_accounts $lender)
    {
        $advices       = [];
        $evaluation    = $this->getCurrentEvaluation($lender);
        $questionTypes = [\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE, \lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS, \lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD];

        /** @var \lender_questionnaire_question $questionEntity */
        $questionEntity = $this->entityManager->getRepository('lender_questionnaire_question');
        $questions      = $questionEntity->select('id_lender_questionnaire = ' . $evaluation->id_lender_questionnaire . ' AND type IN ("' . implode('","', $questionTypes) . '")', '`order` ASC');
        $questions      = array_column($questions, 'id_lender_questionnaire_question', 'type');

        /** @var \lender_evaluation_answer $answerEntity */
        $answerEntity = $this->entityManager->getRepository('lender_evaluation_answer');
        $answers      = $answerEntity->select('id_lender_evaluation = ' . $evaluation->id_lender_evaluation . ' AND id_lender_questionnaire_question IN (' . implode(',', $questions) . ')');
        $answers      = array_column($answers, 'first_answer', 'id_lender_questionnaire_question');

        if (in_array('', $answers)) {
            return null;
        }

        $estate = $answers[$questions[\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE]];

        if ($estate < \lender_questionnaire_question::VALUE_ESTATE_THRESHOLD) {
            $monthlySavings = $answers[$questions[\lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS]];

            if ($monthlySavings < \lender_questionnaire_question::VALUE_MONTHLY_SAVINGS_THRESHOLD) {
                $advices[] = $this->translator->trans('lender-evaluation_low-estate-low-savings-advice');
                return $advices;
            }
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

        $blockingPeriod = $answers[$questions[\lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD]];

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
    public function saveLog(\lender_evaluation $evaluation, $event, $message)
    {
        /** @var \lender_evaluation_log $advice */
        $advice = $this->entityManager->getRepository('lender_evaluation_log');
        $advice->id_lender_evaluation = $evaluation->id_lender_evaluation;
        $advice->event                = $event;
        $advice->message              = $message;
        $advice->create();

        return $advice;
    }

    /**
     * If no evaluation is completed, returns null
     * If evaluation is completed, return list of indicators (suggested limitations)
     * If no limitation is suggested, indicator value is null
     *
     * @param \lenders_accounts $lender
     * @return array|null
     */
    public function getIndicators(\lenders_accounts $lender)
    {
        $evaluation = $this->getCurrentEvaluation($lender);

        if (false === $this->isValidEvaluation($evaluation)) {
            return null;
        }

        // @todo retrieve indicators values
        return [
            self::INDICATOR_TOTAL_AMOUNT     => null,
            self::INDICATOR_AMOUNT_BY_MONTH  => 150,
            self::INDICATOR_PROJECT_DURATION => 36
        ];
    }

    /**
     * @param \bids     $bid
     * @param \projects $project
     * @return bool
     */
    public function isCIPValidationNeeded(\bids $bid, \projects $project)
    {
        $productContracts = $this->productManager->getProjectAvailableContractTypes($project);

        if (false === in_array(\underlying_contract::CONTRACT_MINIBON, array_column($productContracts, 'label'))) {
            return false;
        }

        /** @var \underlying_contract $contract */
        $contract = $this->entityManager->getRepository('underlying_contract');
        if (false === $contract->get(\underlying_contract::CONTRACT_IFP, 'label')) {
            throw new \InvalidArgumentException('The contract ' . \underlying_contract::CONTRACT_IFP . 'does not exist.');
        }

        $contractAttrVars = $this->contractAttributeManager->getContractAttributesByType($contract, \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
        if (empty($contractAttrVars) || false === isset($contractAttrVars[0]) || false === is_numeric($contractAttrVars[0])) {
            throw new \UnexpectedValueException('The IFP contract max amount is not set');
        }

        $thresholdAmount = $contractAttrVars[0];
        $lenderBids      = $bid->select('id_lender_account = ' . $bid->id_lender_account . ' AND id_project = ' . $project->id_project . ' AND status = ' . \bids::STATUS_BID_ACCEPTED);

        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManager->getRepository('lenders_accounts');
        if (false === $lenderAccount->isNaturalPerson($bid->id_lender_account)) {
            return true;
        }

        $totalBidsAmount = 0;
        foreach ($lenderBids as $lenderBid) {
            $totalBidsAmount = bcadd($totalBidsAmount, bcdiv($lenderBid['amount'], 100, 2), 2);
        }
        $totalBidsAmount = bcadd($totalBidsAmount, bcdiv($bid->amount, 100, 2), 2);

        if (bccomp($totalBidsAmount, $thresholdAmount, 2) < 0) {
            return false;
        }

        return true;
    }
}
