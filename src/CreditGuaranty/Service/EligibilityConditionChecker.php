<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Service;

use Doctrine\Common\Collections\Collection;
use LogicException;
use Unilend\Core\Entity\Constant\MathOperator;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityCondition;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityConditionRepository;

class EligibilityConditionChecker
{
    private ProgramEligibilityConditionRepository $programEligibilityConditionRepository;
    private EligibilityHelper $eligibilityHelper;
    private array $ineligibles = [];

    public function __construct(
        ProgramEligibilityConditionRepository $programEligibilityConditionRepository,
        EligibilityHelper $eligibilityHelper
    ) {
        $this->programEligibilityConditionRepository = $programEligibilityConditionRepository;
        $this->eligibilityHelper                     = $eligibilityHelper;
    }

    public function checkByConfiguration(Reservation $reservation, ProgramEligibilityConfiguration $programEligibilityConfiguration): array
    {
        $programEligibilityConditions = $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ]);

        if (0 === count($programEligibilityConditions)) {
            return [];
        }

        foreach ($programEligibilityConditions as $eligibilityCondition) {
            $this->checkCondition($reservation, $eligibilityCondition);
        }

        return $this->ineligibles;
    }

    private function checkCondition(Reservation $reservation, ProgramEligibilityCondition $eligibilityCondition): void
    {
        $operator          = $eligibilityCondition->getOperation();
        $rightOperandField = $eligibilityCondition->getRightOperandField();
        $rightValue        = $eligibilityCondition->getValue();

        if (ProgramEligibilityCondition::VALUE_TYPE_RATE === $eligibilityCondition->getValueType()) {
            if (null === $rightOperandField) {
                throw new LogicException(sprintf('The ProgramEligibilityCondition #%d of rate type should have an rightOperandField.', $eligibilityCondition->getId()));
            }

            $rightEntity = $this->eligibilityHelper->getEntity($reservation, $rightOperandField);

            if ($rightEntity instanceof Collection) {
                throw new LogicException(sprintf('The rightOperandField of ProgramEligibilityCondition #%d cannot be a collection.', $eligibilityCondition->getId()));
            }

            $rightValue = bcmul(
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
                    $this->ineligibles[$leftOperandField->getCategory()][] = $leftOperandField->getFieldAlias();

                    if ($rightOperandField instanceof Field) {
                        $this->ineligibles[$rightOperandField->getCategory()][] = $rightOperandField->getFieldAlias();
                    }
                }
            }

            return;
        }

        $leftValue = $this->eligibilityHelper->getValue($leftEntity, $leftOperandField);

        if (false === $this->check($operator, $leftValue, $rightValue)) {
            $this->ineligibles[$leftOperandField->getCategory()][] = $leftOperandField->getFieldAlias();

            if ($rightOperandField instanceof Field) {
                $this->ineligibles[$rightOperandField->getCategory()][] = $rightOperandField->getFieldAlias();
            }
        }
    }

    private function check(string $operator, $leftValue, $valueToCompare): bool
    {
        $comparison = bccomp((string) $leftValue, (string) $valueToCompare, 4);

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
                throw new LogicException(sprintf('Operator %s unexpected in ProgramEligibilityConditions.', $operator));
        }
    }
}
