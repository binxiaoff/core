<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\Eligibility;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use KLS\Core\Entity\Constant\MathOperator;
use KLS\Core\Entity\Interfaces\MoneyInterface;
use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityCondition;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Entity\Project;
use KLS\CreditGuaranty\FEI\Service\ReservationAccessor;
use LogicException;

class EligibilityConditionChecker
{
    private ReservationAccessor $reservationAccessor;

    public function __construct(ReservationAccessor $reservationAccessor)
    {
        $this->reservationAccessor = $reservationAccessor;
    }

    /**
     * @param object|Borrower|Project|FinancingObject $object
     */
    public function checkByConfiguration(
        object $object,
        ProgramEligibilityConfiguration $programEligibilityConfiguration
    ): bool {
        $programEligibilityConditions = $programEligibilityConfiguration->getProgramEligibilityConditions();

        if (0 === $programEligibilityConditions->count()) {
            return true;
        }

        foreach ($programEligibilityConditions as $eligibilityCondition) {
            if (false === $this->checkCondition($object, $eligibilityCondition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param object|Borrower|Project|FinancingObject $object
     */
    private function checkCondition(object $object, ProgramEligibilityCondition $eligibilityCondition): bool
    {
        $reservation    = $object->getReservation();
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

            $rightValue = $this->reservationAccessor->getValue($rightEntity, $rightOperandField);
            if ($rightValue instanceof MoneyInterface) {
                $rightValue = $rightValue->getAmount();
            }

            $valueToCompare = \bcmul(
                (string) $rightValue,
                $eligibilityCondition->getValue(),
                4
            );
        }

        $leftOperandField = $eligibilityCondition->getLeftOperandField();
        $leftEntity       = $this->reservationAccessor->getEntity($reservation, $leftOperandField);

        if ($leftEntity instanceof Collection) {
            if ($object instanceof FinancingObject) {
                // we use the object used to allow checking eligibility of the real object
                $leftEntity = new ArrayCollection([$object]);
            }

            foreach ($leftEntity as $leftEntityItem) {
                $leftValue = $this->reservationAccessor->getValue($leftEntityItem, $leftOperandField);

                if (false === $this->checkByType($eligibilityCondition, $leftValue, $valueToCompare)) {
                    return false;
                }
            }

            return $leftEntity->count() > 0;
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
        if ($leftValue instanceof MoneyInterface) {
            $leftValue = $leftValue->getAmount();
        }
        if ($valueToCompare instanceof MoneyInterface) {
            $valueToCompare = $valueToCompare->getAmount();
        }

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
