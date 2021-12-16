<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service;

use Doctrine\Common\Collections\Collection;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use LogicException;

class EligibilityChecker
{
    private ProgramEligibilityRepository $programEligibilityRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;
    private ReservationAccessor $reservationAccessor;
    private EligibilityConditionChecker $eligibilityConditionChecker;

    public function __construct(
        ProgramEligibilityRepository $programEligibilityRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository,
        ReservationAccessor $reservationAccessor,
        EligibilityConditionChecker $eligibilityConditionChecker
    ) {
        $this->programEligibilityRepository              = $programEligibilityRepository;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
        $this->reservationAccessor                       = $reservationAccessor;
        $this->eligibilityConditionChecker               = $eligibilityConditionChecker;
    }

    public function check(Reservation $reservation, bool $withConditions, ?string $category): array
    {
        $programEligibilities = $this->programEligibilityRepository->findByProgramAndFieldCategory(
            $reservation->getProgram(),
            $category
        );

        $ineligibles = [];

        foreach ($programEligibilities as $programEligibility) {
            if ($this->checkByField($reservation, $programEligibility, $withConditions)) {
                continue;
            }

            $field = $programEligibility->getField();

            $ineligibles[$field->getCategory()][] = $field->getFieldAlias();
        }

        return $ineligibles;
    }

    private function checkByField(
        Reservation $reservation,
        ProgramEligibility $programEligibility,
        bool $withConditions
    ): bool {
        $field  = $programEligibility->getField();
        $entity = $this->reservationAccessor->getEntity($reservation, $field);

        if ($entity instanceof Collection) {
            foreach ($entity as $entityItem) {
                $value = $this->reservationAccessor->getValue($entityItem, $field);

                if ('Collection' === $field->getPropertyType()) {
                    /** @var ProgramChoiceOption[]|Collection $value */
                    foreach ($value as $valueItem) {
                        $isEligible = $this->isEligible($reservation, $programEligibility, $withConditions, $valueItem);
                        if (false === $isEligible) {
                            return false;
                        }
                    }

                    return 0 < $value->count();
                }

                if (false === $this->isEligible($reservation, $programEligibility, $withConditions, $value)) {
                    return false;
                }
            }

            return true;
        }

        $value = $this->reservationAccessor->getValue($entity, $field);

        if ('Collection' === $field->getPropertyType()) {
            foreach ($value as $valueItem) {
                if (false === $this->isEligible($reservation, $programEligibility, $withConditions, $valueItem)) {
                    return false;
                }
            }

            return 0 < $value->count();
        }

        return $this->isEligible($reservation, $programEligibility, $withConditions, $value);
    }

    private function isEligible(
        Reservation $reservation,
        ProgramEligibility $programEligibility,
        bool $withConditions,
        $value
    ): bool {
        if (false === $this->isValueValid($reservation, $programEligibility, $value)) {
            return false;
        }

        $programEligibilityConfiguration = $this->getConfigurationByTypeAndValue($programEligibility, $value);

        if (false === ($programEligibilityConfiguration instanceof ProgramEligibilityConfiguration)) {
            return false;
        }

        if (false === $programEligibilityConfiguration->isEligible()) {
            return false;
        }

        if (
            $withConditions
            && false === $this->eligibilityConditionChecker->checkByConfiguration(
                $reservation,
                $programEligibilityConfiguration
            )
        ) {
            return false;
        }

        return true;
    }

    private function isValueValid(Reservation $reservation, ProgramEligibility $programEligibility, $value): bool
    {
        $field = $programEligibility->getField();

        // we do not check value
        // if field is a creation_in_progress related field and if borrower is in creation in progress
        if (
            \in_array($field->getFieldAlias(), FieldAlias::CREATION_IN_PROGRESS_RELATED_FIELDS, true)
            && $reservation->getBorrower()->isCreationInProgress()
        ) {
            return true;
        }

        return null !== $value;
    }

    private function getConfigurationByTypeAndValue(
        ProgramEligibility $programEligibility,
        $value
    ): ?ProgramEligibilityConfiguration {
        switch ($programEligibility->getField()->getType()) {
            case Field::TYPE_OTHER:
                return $this->programEligibilityConfigurationRepository->findOneBy([
                    'programEligibility' => $programEligibility,
                ]);

            case Field::TYPE_BOOL:
                return $this->programEligibilityConfigurationRepository->findOneBy([
                    'programEligibility' => $programEligibility,
                    'value'              => (int) $value,
                ]);

            case Field::TYPE_LIST:
                return $this->programEligibilityConfigurationRepository->findOneBy([
                    'programEligibility'  => $programEligibility,
                    'programChoiceOption' => $value,
                ]);

            default:
                // the check is done in ProgramEligibility::initialiseConfigurations
                throw new LogicException('This code should not be reached');
        }
    }
}
