<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Service;

use Doctrine\Common\Collections\Collection;
use KLS\Core\Entity\Constant\MathOperator;
use KLS\CreditGuaranty\Entity\ProgramEligibilityCondition;
use KLS\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\Entity\Reservation;
use KLS\CreditGuaranty\Repository\ProgramEligibilityConditionRepository;
use LogicException;

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
        $operator          = $eligibilityCondition->getOperation();
        $rightOperandField = $eligibilityCondition->getRightOperandField();
        $rightValue        = $eligibilityCondition->getValue();

        if (ProgramEligibilityCondition::VALUE_TYPE_RATE === $eligibilityCondition->getValueType()) {
            if (null === $rightOperandField) {
                throw new LogicException(\sprintf('The ProgramEligibilityCondition #%d of rate type should have an rightOperandField.', $eligibilityCondition->getId()));
            }

            $rightEntity = $this->eligibilityHelper->getEntity($reservation, $rightOperandField);

            if ($rightEntity instanceof Collection) {
                throw new LogicException(\sprintf('The rightOperandField of ProgramEligibilityCondition #%d cannot be a collection.', $eligibilityCondition->getId()));
            }

            $rightValue = \bcmul(
                (string) $this->eligibilityHelper->getValue($rightEntity, $rightOperandField),
                $eligibilityCondition->getValue(),
                4
            );
        }

        $leftOperandField = $eligibilityCondition->getLeftOperandField();
        $leftEntity       = $this->eligibilityHelper->getEntity($reservation, $leftOperandField);

        if ($leftEntity instanceof Collection) {
            foreach ($leftEntity as $leftEntityItem) {
                $leftValue = $this->eligibilityHelper->getValue($leftEntityItem, $leftOperandField);

                if (false === $this->check($operator, $leftValue, $rightValue)) {
                    return false;
                }
            }

            return true;
        }

        $leftValue = $this->eligibilityHelper->getValue($leftEntity, $leftOperandField);

        return $this->check($operator, $leftValue, $rightValue);
    }

    private function check(string $operator, $leftValue, $valueToCompare): bool
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
                throw new LogicException(\sprintf('Operator %s unexpected in ProgramEligibilityConditions.', $operator));
        }
    }
}
