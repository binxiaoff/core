<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\core\Loader;

class CIPManager
{
    const INDICATOR_TOTAL_AMOUNT             = 'total_amount';
    const INDICATOR_AMOUNT_BY_MONTH          = 'amount_by_month';
    const INDICATOR_PROJECT_DURATION         = 'project_duration';
    const INDICATOR_PROJECT_DURATION_1_YEAR  = 12;
    const INDICATOR_PROJECT_DURATION_3_YEARS = 36;
    const INDICATOR_PROJECT_DURATION_5_YEARS = 60;

    /** @var ProductManager */
    private $productManager;

    /** @var ContractManager */
    private $contractManager;

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /** @var TranslatorInterface */
    private $translator;

    /** @var EntityManager */
    private $entityManager;

    /**
     * @param ProductManager         $productManager
     * @param ContractManager        $contractManager
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param TranslatorInterface    $translator
     * @param EntityManager          $entityManager
     */
    public function __construct(
        ProductManager $productManager,
        ContractManager $contractManager,
        EntityManagerSimulator $entityManagerSimulator,
        TranslatorInterface $translator,
        EntityManager $entityManager
    ) {
        $this->productManager         = $productManager;
        $this->contractManager        = $contractManager;
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->translator             = $translator;
        $this->entityManager          = $entityManager;
    }

    /**
     * @return \lender_questionnaire
     * @throws \Exception
     */
    public function getCurrentQuestionnaire()
    {
        /** @var \lender_questionnaire $questionnaire */
        $questionnaire = $this->entityManagerSimulator->getRepository('lender_questionnaire');

        if (false === $questionnaire->get(\lender_questionnaire::STATUS_ACTIVE, 'status')) {
            throw new \Exception('Unable to create lender evaluation. No single active questionnaire found');
        }

        return $questionnaire;
    }

    /**
     * @param Clients $client
     *
     * @return \lender_evaluation|null
     * @throws \Exception
     */
    public function getCurrentEvaluation(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        /** @var \lender_evaluation $evaluation */
        $evaluation    = $this->entityManagerSimulator->getRepository('lender_evaluation');
        $questionnaire = $this->getCurrentQuestionnaire();

        if ($evaluation->get($wallet->getId(),
            '(expiry_date = "0000-00-00" OR expiry_date > NOW()) AND id_lender_questionnaire = ' . $questionnaire->id_lender_questionnaire . ' AND id_lender')
        ) {
            return $evaluation;
        }

        return null;
    }

    /**
     * @param Clients $client
     *
     * @return bool
     * @throws \Exception
     */
    public function hasValidEvaluation(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $evaluation = $this->getCurrentEvaluation($client);

        if (null === $evaluation) {
            return false;
        }

        return $this->isValidEvaluation($evaluation);
    }

    /**
     * @param \lender_evaluation $evaluation
     *
     * @return bool
     * @throws \Exception
     */
    public function isEvaluationStarted(\lender_evaluation $evaluation)
    {
        return (null !== $this->getLastQuestion($evaluation));
    }

    /**
     * @param \lender_evaluation $evaluation
     *
     * @return bool
     */
    public function isValidEvaluation(\lender_evaluation $evaluation)
    {
        return (
            $evaluation->expiry_date !== '0000-00-00 00:00:00'
            && new \DateTime('NOW') < \DateTime::createFromFormat('Y-m-d H:i:s', $evaluation->expiry_date)
        );
    }

    /**
     * @param Clients $client
     *
     * @return \lender_evaluation|null
     * @throws \Exception
     */
    public function createEvaluation(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        /** @var Wallet $wallet */
        $wallet     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $evaluation = $this->getCurrentEvaluation($client);

        if (null === $evaluation) {
            /** @var \lender_evaluation $evaluation */
            $evaluation                          = $this->entityManagerSimulator->getRepository('lender_evaluation');
            $evaluation->id_lender_questionnaire = $this->getCurrentQuestionnaire()->id_lender_questionnaire;
            $evaluation->id_lender               = $wallet->getId();
            $evaluation->create();
        }

        return $evaluation;
    }

    /**
     * @param Clients $client
     *
     * @throws \Exception
     */
    public function startEvaluation(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $evaluation = $this->getCurrentEvaluation($client);

        if (null === $evaluation) {
            $evaluation = $this->createEvaluation($client);
        }

        if (false === $this->isEvaluationStarted($evaluation)) {
            /** @var \lender_questionnaire_question $question */
            $question  = $this->entityManagerSimulator->getRepository('lender_questionnaire_question');
            $questions = $question->select('id_lender_questionnaire = ' . $evaluation->id_lender_questionnaire, '`order` ASC', 0, 1)[0];

            /** @var \lender_evaluation_answer $answer */
            $answer                                   = $this->entityManagerSimulator->getRepository('lender_evaluation_answer');
            $answer->status                           = \lender_evaluation_answer::STATUS_ACTIVE;
            $answer->id_lender_evaluation             = $evaluation->id_lender_evaluation;
            $answer->id_lender_questionnaire_question = $questions['id_lender_questionnaire_question'];
            $answer->create();
        }
    }

    /**
     * @param \lender_evaluation $evaluation
     */
    public function endEvaluation(\lender_evaluation $evaluation)
    {
        $evaluation->expiry_date = date('Y-m-d H:i:s');
        $evaluation->update();
    }

    /**
     * @param \lender_evaluation $evaluation
     */
    public function validateEvaluation(\lender_evaluation $evaluation)
    {
        $expiryDate              = new \DateTime('NOW + 1 YEAR');
        $evaluation->expiry_date = $expiryDate->format('Y-m-d H:i:s');
        $evaluation->update();
    }

    /**
    public function endLenderEvaluation(Clients $client)
    {
        $evaluation = $this->getCurrentEvaluation($client);
        if (null !== $evaluation) {
            $this->endEvaluation($evaluation);
        }
    }

    /**
     * @param Clients $client
     */
    public function validateLenderEvaluation(Clients $client)
    {
        $evaluation = $this->getCurrentEvaluation($client);
        if (null !== $evaluation) {
            $this->validateEvaluation($evaluation);
        }
    }

    /**
     * @param \lender_evaluation $evaluation
     *
     * @return \lender_questionnaire_question|null
     */
    public function getLastQuestion(\lender_evaluation $evaluation)
    {
        /** @var \lender_questionnaire_question $questionEntity */
        $questionEntity = $this->entityManagerSimulator->getRepository('lender_questionnaire_question');
        $questions      = $questionEntity->select('id_lender_questionnaire = ' . $evaluation->id_lender_questionnaire, '`order` ASC');
        $lastQuestionId = null;

        /** @var \lender_evaluation_answer $answerEntity */
        $answerEntity   = $this->entityManagerSimulator->getRepository('lender_evaluation_answer');
        $answers        = $answerEntity->select('id_lender_evaluation = ' . $evaluation->id_lender_evaluation . ' AND status = ' . \lender_evaluation_answer::STATUS_ACTIVE,
            'added ASC, id_lender_evaluation_answer ASC');
        $indexedAnswers = [];

        foreach ($answers as $index => $answer) {
            $indexedAnswers[$answer['id_lender_questionnaire_question']] = $answer;
        }

        foreach ($questions as $index => $question) {
            if (isset($indexedAnswers[$question['id_lender_questionnaire_question']])) {
                $lastQuestionId = $question['id_lender_questionnaire_question'];
            }
        }

        if (null !== $lastQuestionId) {
            $questionEntity->get($lastQuestionId);
            return $questionEntity;
        }

        return null;
    }

    /**
     * @param \lender_evaluation             $evaluation
     * @param \lender_questionnaire_question $question
     *
     * @return \lender_evaluation_answer|null
     */
    public function insertQuestion(\lender_evaluation $evaluation, \lender_questionnaire_question $question)
    {
        /** @var \lender_evaluation_answer $answer */
        $answer                                   = $this->entityManagerSimulator->getRepository('lender_evaluation_answer');
        $answer->status                           = \lender_evaluation_answer::STATUS_ACTIVE;
        $answer->id_lender_evaluation             = $evaluation->id_lender_evaluation;
        $answer->id_lender_questionnaire_question = $question->id_lender_questionnaire_question;
        $answer->create();

        return $answer;
    }

    /**
     * @param \lender_evaluation $evaluation
     *
     * @return \lender_questionnaire_question|null
     */
    public function getNextQuestion(\lender_evaluation $evaluation)
    {
        /** @var \lender_questionnaire_question $questionEntity */
        $questionEntity = $this->entityManagerSimulator->getRepository('lender_questionnaire_question');
        $questions      = $questionEntity->select('id_lender_questionnaire = ' . $evaluation->id_lender_questionnaire, '`order` ASC');
        $lastQuestion   = $this->getLastQuestion($evaluation);
        $nextQuestion   = false;

        foreach ($questions as $question) {
            if ($nextQuestion) {
                if (\lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS === $question['type']) {
                    $questionEntity->get(\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE, 'type');
                    $answer = $this->getAnswer($evaluation, $questionEntity);

                    if (null === $answer) {
                        return null;
                    }

                    if ($answer->first_answer >= \lender_questionnaire_question::VALUE_ESTATE_THRESHOLD) {
                        continue;
                    }
                }
                $questionEntity->get($question['id_lender_questionnaire_question']);
                return $questionEntity;
            }
            if ($question['id_lender_questionnaire_question'] == $lastQuestion->id_lender_questionnaire_question) {
                $nextQuestion = true;
            }
        }

        return null;
    }

    /**
     * @param \lender_evaluation             $evaluation
     * @param \lender_questionnaire_question $question
     *
     * @return \lender_evaluation_answer|null
     */
    public function getAnswer(\lender_evaluation $evaluation, \lender_questionnaire_question $question)
    {
        /** @var \lender_evaluation_answer $answer */
        $answer = $this->entityManagerSimulator->getRepository('lender_evaluation_answer');

        if (false === $answer->get($evaluation->id_lender_evaluation,
                'id_lender_questionnaire_question = ' . $question->id_lender_questionnaire_question . ' AND status = ' . \lender_evaluation_answer::STATUS_ACTIVE . ' AND id_lender_evaluation')
        ) {
            return null;
        }

        return $answer;
    }

    /**
     * @param \lender_evaluation $evaluation
     *
     * @return array
     */
    public function getAnswersByType(\lender_evaluation $evaluation)
    {
        /** @var \lender_questionnaire_question $questionEntity */
        $questionEntity   = $this->entityManagerSimulator->getRepository('lender_questionnaire_question');
        $questions        = $questionEntity->select('id_lender_questionnaire = ' . $evaluation->id_lender_questionnaire, '`order` ASC');
        $indexedQuestions = [];
        $indexedAnswers   = [];

        foreach ($questions as $question) {
            $indexedQuestions[$question['id_lender_questionnaire_question']] = $question;
        }

        /** @var \lender_evaluation_answer $answerEntity */
        $answerEntity = $this->entityManagerSimulator->getRepository('lender_evaluation_answer');
        $answers      = $answerEntity->select('id_lender_evaluation = ' . $evaluation->id_lender_evaluation . ' AND status = ' . \lender_evaluation_answer::STATUS_ACTIVE,
            'added ASC, id_lender_evaluation_answer ASC');

        foreach ($answers as $answer) {
            $indexedAnswers[$indexedQuestions[$answer['id_lender_questionnaire_question']]['type']] = $answer;
        }

        return $indexedAnswers;
    }

    /**
     * @param \lender_questionnaire_question $question
     * @param \lender_evaluation             $evaluation
     * @param string                         $value
     *
     * @return \lender_evaluation_answer|null
     */
    public function saveAnswer(\lender_questionnaire_question $question, \lender_evaluation $evaluation, $value = '')
    {
        if (\lender_questionnaire_question::TYPE_VALUE_OTHER_FINANCIAL_PRODUCTS_USE == $question->type) {
            $value = json_encode(empty($value) ? [] : $value);
        }

        $answer = $this->getAnswer($evaluation, $question);

        if (null !== $answer) {
            // Do not switch first answer and second answer assignment
            $answer->second_answer = empty($answer->first_answer) ? '' : $value;
            $answer->first_answer  = empty($answer->first_answer) ? $value : $answer->first_answer;

            if (false === $this->isValidAnswer($answer, $question)) {
                return null;
            }

            $answer->update();

            if (
                false === $question->isBooleanType($question->type)
                || \lender_questionnaire_question::TYPE_AWARE_DIVIDE_INVESTMENTS === $question->type && \lender_questionnaire_question::VALUE_BOOLEAN_TRUE === $answer->first_answer && \lender_questionnaire_question::VALUE_BOOLEAN_FALSE === $answer->second_answer
                || \lender_questionnaire_question::TYPE_AWARE_DIVIDE_INVESTMENTS !== $question->type && \lender_questionnaire_question::VALUE_BOOLEAN_FALSE === $answer->first_answer && \lender_questionnaire_question::VALUE_BOOLEAN_TRUE === $answer->second_answer
            ) {
                $nextQuestion = $this->getNextQuestion($evaluation);

                if (null !== $nextQuestion) {
                    $this->insertQuestion($evaluation, $this->getNextQuestion($evaluation));
                }
            }

            return $answer;
        }

        return null;
    }

    /**
     * @param \lender_evaluation_answer      $answer
     * @param \lender_questionnaire_question $question
     *
     * @return bool
     */
    private function isValidAnswer(\lender_evaluation_answer $answer, \lender_questionnaire_question $question)
    {
        if (null === $question || $answer->id_lender_questionnaire_question != $question->id_lender_questionnaire_question) {
            /** @var \lender_questionnaire_question $question */
            $question = $this->entityManagerSimulator->getRepository('lender_questionnaire_question');
            $question->get($answer->id_lender_questionnaire_question);
        }

        if ($question->isBooleanType($question->type)) {
            return (
                in_array($answer->first_answer, [\lender_questionnaire_question::VALUE_BOOLEAN_TRUE, \lender_questionnaire_question::VALUE_BOOLEAN_FALSE])
                && in_array($answer->second_answer, [\lender_questionnaire_question::VALUE_BOOLEAN_TRUE, \lender_questionnaire_question::VALUE_BOOLEAN_FALSE, ''])
            );
        }

        return ('' !== $answer->first_answer);
    }

    /**
     * @param \lender_evaluation $evaluation
     */
    public function resetValues(\lender_evaluation $evaluation)
    {
        /** @var \lender_questionnaire_question $question */
        $question = $this->entityManagerSimulator->getRepository('lender_questionnaire_question');
        /** @var \lender_evaluation_answer $answerEntity */
        $answerEntity = $this->entityManagerSimulator->getRepository('lender_evaluation_answer');
        $answers      = $this->getAnswersByType($evaluation);

        foreach ($answers as $type => $answer) {
            if (false === $question->isBooleanType($type)) {
                $answerEntity->get($answer['id_lender_evaluation_answer']);
                $answerEntity->status = \lender_evaluation_answer::STATUS_INACTIVE;
                $answerEntity->update();
            }
        }

        /** @var \lender_questionnaire_question $question */
        $question = $this->entityManagerSimulator->getRepository('lender_questionnaire_question');
        $question->get(\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE, 'id_lender_questionnaire = ' . $evaluation->id_lender_questionnaire . ' AND type');

        $this->insertQuestion($evaluation, $question);
    }

    /**
     * @param Clients $client
     *
     * @return string[]|null
     */
    public function getAdvices(Clients $client)
    {
        $advices    = [];
        $indicators = $this->getIndicators($client, false);

        if (null === $indicators) {
            return null;
        }

        if (null === $indicators[self::INDICATOR_TOTAL_AMOUNT] && null === $indicators[self::INDICATOR_AMOUNT_BY_MONTH]) {
            $advices[] = $this->translator->trans('lender-evaluation_low-estate-low-savings-advice');
            return $advices;
        }

        if (null !== $indicators[self::INDICATOR_TOTAL_AMOUNT]) {
            /** @var \ficelle $ficelle */
            $ficelle   = Loader::loadLib('ficelle');

            if ($indicators[self::INDICATOR_TOTAL_AMOUNT] < \lender_questionnaire_question::VALUE_ESTATE_THRESHOLD * 0.101) {
                $advices[] = $this->translator->trans('lender-evaluation_low-estate-low-boundary-advice', [
                    '%maximumAmount%'    => $ficelle->formatNumber($indicators[self::INDICATOR_TOTAL_AMOUNT], 0),
                    '%maximumAmount100%' => $ficelle->formatNumber(floor($indicators[self::INDICATOR_TOTAL_AMOUNT] / 100), 0)
                ]);
            } elseif (floor($indicators[self::INDICATOR_TOTAL_AMOUNT] / 200) < 20) {
                $advices[] = $this->translator->trans('lender-evaluation_low-estate-low-loan-advice', [
                    '%maximumAmount%'    => $ficelle->formatNumber($indicators[self::INDICATOR_TOTAL_AMOUNT], 0),
                    '%maximumAmount100%' => $ficelle->formatNumber(floor($indicators[self::INDICATOR_TOTAL_AMOUNT] / 100), 0)
                ]);
            } else {
                $advices[] = $this->translator->trans('lender-evaluation_low-estate-advice', [
                    '%maximumAmount%'    => $ficelle->formatNumber($indicators[self::INDICATOR_TOTAL_AMOUNT], 0),
                    '%maximumAmount100%' => $ficelle->formatNumber(floor($indicators[self::INDICATOR_TOTAL_AMOUNT] / 100), 0),
                    '%maximumAmount200%' => $ficelle->formatNumber(floor($indicators[self::INDICATOR_TOTAL_AMOUNT] / 200), 0)
                ]);
            }
        } elseif (null !== $indicators[self::INDICATOR_AMOUNT_BY_MONTH]) {
            /** @var \ficelle $ficelle */
            $ficelle   = Loader::loadLib('ficelle');
            $advices[] = $this->translator->trans('lender-evaluation_low-savings-advice', [
                '%maximumAmount%' => $ficelle->formatNumber($indicators[self::INDICATOR_AMOUNT_BY_MONTH], 0)
            ]);
        }

        switch ($indicators[self::INDICATOR_PROJECT_DURATION]) {
            case self::INDICATOR_PROJECT_DURATION_1_YEAR:
                $advices[] = $this->translator->trans('lender-evaluation_blocking-period-less-1-year-advice');
                break;
            case self::INDICATOR_PROJECT_DURATION_3_YEARS:
                $advices[] = $this->translator->trans('lender-evaluation_blocking-period-less-3-years-advice');
                break;
            case self::INDICATOR_PROJECT_DURATION_5_YEARS:
                $advices[] = $this->translator->trans('lender-evaluation_blocking-period-less-5-years-advice');
                break;
            case null:
                $advices[] = $this->translator->trans('lender-evaluation_blocking-period-more-5-years-advice');
                break;
        }

        return $advices;
    }

    /**
     * If no evaluation is completed, returns null
     * If evaluation is completed, return list of indicators (suggested limitations)
     * If no limitation is suggested, indicator value is null
     *
     * @param Clients $clients
     * @param bool    $checkEvaluationValidity
     *
     * @return array|null
     */
    public function getIndicators(Clients $clients, $checkEvaluationValidity = true)
    {
        $evaluation = $this->getCurrentEvaluation($clients);

        if (null === $evaluation || $checkEvaluationValidity && false === $this->isValidEvaluation($evaluation)) {
            return null;
        }

        $answers = $this->getAnswersByType($evaluation);

        if (false === isset($answers[\lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD])) {
            return null;
        }

        $totalAmountIndicator     = null;
        $amountByMonthIndicator   = null;
        $projectDurationIndicator = null;
        $estate                   = $answers[\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE]['first_answer'];
        $monthlySavings           = isset($answers[\lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS]) ? $answers[\lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS]['first_answer'] : 0;
        $blockingPeriod           = $answers[\lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD]['first_answer'];

        if ($estate >= \lender_questionnaire_question::VALUE_ESTATE_THRESHOLD) {
            $totalAmountIndicator = floor($estate / 10);
        } elseif ($monthlySavings >= \lender_questionnaire_question::VALUE_MONTHLY_SAVINGS_THRESHOLD) {
            $amountByMonthIndicator = floor($monthlySavings / 200) * 20;
        }

        switch ($blockingPeriod) {
            case \lender_questionnaire_question::VALUE_BLOCKING_PERIOD_1:
                $projectDurationIndicator = 12;
                break;
            case \lender_questionnaire_question::VALUE_BLOCKING_PERIOD_2:
                $projectDurationIndicator = 36;
                break;
            case \lender_questionnaire_question::VALUE_BLOCKING_PERIOD_3:
                $projectDurationIndicator = 60;
                break;
        }

        return [
            self::INDICATOR_TOTAL_AMOUNT     => $totalAmountIndicator,
            self::INDICATOR_AMOUNT_BY_MONTH  => $amountByMonthIndicator,
            self::INDICATOR_PROJECT_DURATION => $projectDurationIndicator
        ];
    }

    /**
     * @param \bids $bid
     *
     * @return bool
     */
    public function isCIPValidationNeeded(\bids $bid)
    {
        /** @var \projects $project */
        $project = $this->entityManagerSimulator->getRepository('projects');
        $project->get($bid->id_project);

        /** @var \product $product */
        $product = $this->entityManagerSimulator->getRepository('product');
        $product->get($project->id_product);

        $productContracts = $this->productManager->getAvailableContracts($product);

        if (false === in_array(\underlying_contract::CONTRACT_MINIBON, array_column($productContracts, 'label'))) {
            return false;
        }

        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($bid->id_lender_account);

        if (false === $wallet->getIdClient()->isNaturalPerson()) {
            return true;
        }

        $thresholdAmount = $this->getContractThresholdAmount();
        $lenderBids      = $bid->sum('
                id_lender_account = ' . $bid->id_lender_account . ' 
                AND id_project = ' . $project->id_project . ' 
                AND status IN (' . \bids::STATUS_BID_PENDING . ', ' . \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY . ')',
            'ROUND(amount / 100)'
        );

        $totalAmount = bcdiv($bid->amount, 100, 2);
        $totalAmount = bcadd($totalAmount, (string) $lenderBids, 2);

        if (bccomp($totalAmount, $thresholdAmount, 2) <= 0) {
            return false;
        }

        return true;
    }

    /**
     * @param \lender_evaluation $evaluation
     * @param string             $event
     * @param string             $message
     *
     * @return \lender_evaluation_log
     */
    public function saveLog(\lender_evaluation $evaluation, $event, $message = '')
    {
        /** @var \lender_evaluation_log $advice */
        $advice                       = $this->entityManagerSimulator->getRepository('lender_evaluation_log');
        $advice->id_lender_evaluation = $evaluation->id_lender_evaluation;
        $advice->event                = $event;
        $advice->message              = $message;
        $advice->create();

        return $advice;
    }

    /**
     * @return int|float
     */
    private function getContractThresholdAmount()
    {
        /** @var \underlying_contract $contract */
        $contract = $this->entityManagerSimulator->getRepository('underlying_contract');
        if (false === $contract->get(\underlying_contract::CONTRACT_IFP, 'label')) {
            throw new \InvalidArgumentException('The contract ' . \underlying_contract::CONTRACT_IFP . 'does not exist.');
        }

        $contractAttrVars = $this->contractManager->getAttributesByType($contract, \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
        if (empty($contractAttrVars) || false === isset($contractAttrVars[0]) || false === is_numeric($contractAttrVars[0])) {
            throw new \UnexpectedValueException('The IFP contract max amount is not set');
        }

        return $contractAttrVars[0];
    }
}
