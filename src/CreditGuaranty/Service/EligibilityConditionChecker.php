<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Service;

use Doctrine\Common\Collections\Collection;
use LogicException;
use Unilend\Core\Entity\Constant\MathOperator;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityCondition;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityConditionRepository;

class EligibilityConditionChecker
{
    private ProgramEligibilityConditionRepository $programEligibilityConditionRepository;
    private EligibilityHelper $eligibilityHelper;

    public function __construct(
        ProgramEligibilityConditionRepository $programEligibilityConditionRepository,
        EligibilityHelper $eligibilityHelper
    ) {
        $this->programEligibilityConditionRepository = $programEligibilityConditionRepository;
        $this->eligibilityHelper                     = $eligibilityHelper;
    }

    public function checkByConfiguration(Reservation $reservation, ProgramEligibilityConfiguration $programEligibilityConfiguration): bool
    {
        $programEligibilityConditions = $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ]);

        if (0 === count($programEligibilityConditions)) {
            return true;
        }

        /** @var ProgramEligibilityCondition $eligibilityCondition */
        foreach ($programEligibilityConditions as $eligibilityCondition) {
            $leftOperandField = $eligibilityCondition->getLeftOperandField();
            $leftEntity       = $this->eligibilityHelper->getEntity($reservation, $leftOperandField);

            if ($leftEntity instanceof Collection) {
                foreach ($leftEntity as $leftEntityItem) {
                    if (false === $this->checkByConditionAndEntity($reservation, $eligibilityCondition, $leftEntityItem)) {
                        return false;
                    }
                }

                return true;
            }

            if (false === $this->checkByConditionAndEntity($reservation, $eligibilityCondition, $leftEntity)) {
                return false;
            }
        }

        return true;
    }

    private function checkByConditionAndEntity(Reservation $reservation, ProgramEligibilityCondition $eligibilityCondition, $leftEntity): bool
    {
        $leftValue = $this->eligibilityHelper->getValue($reservation->getProgram(), $leftEntity, $eligibilityCondition->getLeftOperandField());

        if (
            ProgramEligibilityCondition::VALUE_TYPE_VALUE === $eligibilityCondition->getValueType()
            && false === $this->checkOperation($eligibilityCondition->getOperation(), $leftValue, $eligibilityCondition->getValue())
        ) {
            return false;
        }

        if (ProgramEligibilityCondition::VALUE_TYPE_RATE === $eligibilityCondition->getValueType()) {
            $rightOperandField = $eligibilityCondition->getRightOperandField();

            if (null === $rightOperandField) {
                throw new LogicException(sprintf('The ProgramEligibilityCondition #%s of rate type should have an rightOperandField.', $eligibilityCondition->getId()));
            }

            $rightEntity = $this->eligibilityHelper->getEntity($reservation, $rightOperandField);

            if ($rightEntity instanceof Collection) {
                foreach ($rightEntity->toArray() as $rightItem) {
                    $rightValue     = $this->eligibilityHelper->getValue($reservation->getProgram(), $rightItem, $rightOperandField);
                    $valueToCompare = $rightValue * $eligibilityCondition->getValue();

                    if (false === $this->checkOperation($eligibilityCondition->getOperation(), $rightValue, $valueToCompare)) {
                        return false;
                    }
                }

                return true;
            }

            $rightValue     = $this->eligibilityHelper->getValue($reservation->getProgram(), $rightEntity, $rightOperandField);
            $valueToCompare = $rightValue * $eligibilityCondition->getValue();

            if (false === $this->checkOperation($eligibilityCondition->getOperation(), $rightValue, $valueToCompare)) {
                return false;
            }
        }

        return true;
    }

    private function checkOperation(string $operator, $leftValue, $valueToCompare): bool
    {
        switch ($operator) {
            case MathOperator::INFERIOR:
                return $leftValue < $valueToCompare;

            case MathOperator::INFERIOR_OR_EQUAL:
                return $leftValue <= $valueToCompare;

            case MathOperator::SUPERIOR:
                return $leftValue > $valueToCompare;

            case MathOperator::SUPERIOR_OR_EQUAL:
                return $leftValue >= $valueToCompare;

            case MathOperator::EQUAL:
                return $leftValue == $valueToCompare;

            default:
                throw new LogicException(sprintf('Operator %s unexpected in ProgramEligibilityConditions.', $operator));
        }
    }
}
