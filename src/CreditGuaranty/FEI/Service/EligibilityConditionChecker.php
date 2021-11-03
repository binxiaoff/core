<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service;

use Doctrine\Common\Collections\Collection;
use KLS\Core\Entity\Constant\MathOperator;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityCondition;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConditionRepository;
use LogicException;

class EligibilityConditionChecker
{
    private ProgramEligibilityConditionRepository $programEligibilityConditionRepository;
    private ReservationAccessor $reservationAccessor;

    public function __construct(
        ProgramEligibilityConditionRepository $programEligibilityConditionRepository,
        ReservationAccessor $reservationAccessor
    ) {
        $this->programEligibilityConditionRepository = $programEligibilityConditionRepository;
        $this->reservationAccessor                   = $reservationAccessor;
    }

    public function checkByConfiguration(
        Reservation $reservation,
        ProgramEligibilityConfiguration $programEligibilityConfiguration
    ): bool {
        $programEligibilityConditions = $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ]);

        if (0 === \count($programEligibilityConditions)) {
            return true;
        }

        foreach ($programEligibilityConditions as $eligibilityCondition) {
            if (false === $this->checkCondition($reservation, $eligibilityCondition)) {
                return false;
            }
        }

        return true;
    }

    private function checkCondition(Reservation $reservation, ProgramEligibilityCondition $eligibilityCondition): bool
    {
        $valueToCompare = $eligibilityCondition->getValue() ?? $eligibilityCondition->getProgramChoiceOptions();

        if (ProgramEligibilityCondition::VALUE_TYPE_RATE === $eligibilityCondition->getValueType()) {
            $rightOperandField = $eligibilityCondition->getRightOperandField();

            if (null === $rightOperandField) {
                $message = 'Impossible to check eligibility, ' .
                    'rightOperandField is missing for ProgramEligibilityCondition #%d of a rate valueType';

                throw new LogicException(\sprintf($message, $eligibilityCondition->getId()));
            }

            $rightEntity = $this->reservationAccessor->getEntity($reservation, $rightOperandField);

            if ($rightEntity instanceof Collection) {
                $message = 'Impossible to check eligibility, ' .
                    'rightOperandField of ProgramEligibilityCondition #%d cannot be a Collection type.';

                throw new LogicException(\sprintf($message, $eligibilityCondition->getId()));
            }

            $valueToCompare = \bcmul(
                (string) $this->reservationAccessor->getValue($rightEntity, $rightOperandField),
                $eligibilityCondition->getValue(),
                4
            );
        }

        $leftOperandField = $eligibilityCondition->getLeftOperandField();
        $leftEntity       = $this->reservationAccessor->getEntity($reservation, $leftOperandField);

        if ($leftEntity instanceof Collection) {
            foreach ($leftEntity as $leftEntityItem) {
                $leftValue = $this->reservationAccessor->getValue($leftEntityItem, $leftOperandField);

                if (false === $this->checkByType($eligibilityCondition, $leftValue, $valueToCompare)) {
                    return false;
                }
            }

            return true;
        }

        $leftValue = $this->reservationAccessor->getValue($leftEntity, $leftOperandField);

        return $this->checkByType($eligibilityCondition, $leftValue, $valueToCompare);
    }

    private function checkByType(
        ProgramEligibilityCondition $programEligibilityCondition,
        $leftValue,
        $valueToCompare
    ): bool {
        $valueType = $programEligibilityCondition->getValueType();
        $operation = $programEligibilityCondition->getOperation();

        switch ($valueType) {
            case ProgramEligibilityCondition::VALUE_TYPE_VALUE:
            case ProgramEligibilityCondition::VALUE_TYPE_RATE:
                return $this->checkNumber($operation, $leftValue, $valueToCompare);

            case ProgramEligibilityCondition::VALUE_TYPE_BOOL:
                return $leftValue === (bool) $valueToCompare;

            case ProgramEligibilityCondition::VALUE_TYPE_LIST:
                return $this->checkList($programEligibilityCondition, $leftValue, $valueToCompare);

            default:
                // the check is done in ProgramEligibilityCondition::getAvailableValueTypes
                throw new LogicException('This code should not be reached');
        }
    }

    private function checkNumber(string $operator, $leftValue, $valueToCompare): bool
    {
        $comparison = \bccomp((string) $leftValue, (string) $valueToCompare, 4);

        switch ($operator) {
            case MathOperator::INFERIOR:
                return -1 === $comparison;

            case MathOperator::INFERIOR_OR_EQUAL:
                return -1 === $comparison || 0 === $comparison;

            case MathOperator::SUPERIOR:
                return 1 === $comparison;

            case MathOperator::SUPERIOR_OR_EQUAL:
                return 1 === $comparison || 0 === $comparison;

            case MathOperator::EQUAL:
                return 0 === $comparison;

            default:
                // the check is done in ProgramEligibilityCondition::getAvailableOperations
                throw new LogicException('This code should not be reached');
        }
    }

    private function checkList(
        ProgramEligibilityCondition $programEligibilityCondition,
        $leftValue,
        Collection $valueToCompare
    ): bool {
        if ($leftValue instanceof Collection) {
            foreach ($leftValue as $leftValueItem) {
                if (false === ($leftValueItem instanceof ProgramChoiceOption)) {
                    $message = 'Impossible to check eligibility, ' .
                        'the leftOperandField value is not a ProgramChoiceOption type ' .
                        'for ProgramEligibilityCondition #%d of a list valueType.';

                    throw new LogicException(\sprintf($message, $programEligibilityCondition->getId()));
                }

                foreach ($valueToCompare as $valueToCompareItem) {
                    if ($leftValueItem === $valueToCompareItem) {
                        return true;
                    }
                }
            }

            return 0 < $leftValue->count();
        }

        foreach ($valueToCompare as $valueToCompareItem) {
            if ($leftValue === $valueToCompareItem) {
                return true;
            }
        }

        return false;
    }
}
