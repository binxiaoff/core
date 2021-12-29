<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\Eligibility;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\Common\Collections\Collection;
use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Entity\Project;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\FEI\Service\ReservationAccessor;
use LogicException;

class EligibilityChecker
{
    private IriConverterInterface $iriConverter;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;
    private ReservationAccessor $reservationAccessor;
    private EligibilityConditionChecker $eligibilityConditionChecker;

    public function __construct(
        IriConverterInterface $iriConverter,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository,
        ReservationAccessor $reservationAccessor,
        EligibilityConditionChecker $eligibilityConditionChecker
    ) {
        $this->iriConverter                              = $iriConverter;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
        $this->reservationAccessor                       = $reservationAccessor;
        $this->eligibilityConditionChecker               = $eligibilityConditionChecker;
    }

    public function check(Reservation $reservation): array
    {
        $program = $reservation->getProgram();

        $categories = \array_unique(
            $program->getProgramEligibilities()
                ->map(static fn (ProgramEligibility $pe) => $pe->getFieldCategory())
                ->toArray()
        );

        // This variable will allow saving the different objects of related fields
        // to avoid retrieving them at each iteration
        $objects         = [];
        $ineligibilities = [];

        foreach ($categories as $category) {
            $programEligibilities = $program->getProgramEligibilities()->filter(
                fn (ProgramEligibility $pe) => $category === $pe->getField()->getCategory()
            );

            if (0 === $programEligibilities->count()) {
                $ineligibilities[$category] = [];

                continue;
            }

            if (empty($objects[$category])) {
                $objects[$category] = $this->reservationAccessor->getEntity(
                    $reservation,
                    $programEligibilities->first()->getField()
                );
            }

            $object = $objects[$category];

            if ($object instanceof Collection) {
                if (0 === $object->count()) {
                    $ineligibilities[$category] = [];

                    continue;
                }

                foreach ($object as $objectItem) {
                    $categoryIneligibles = $this->checkByObject($objectItem, $programEligibilities);

                    if (false === empty($categoryIneligibles)) {
                        $iri = $this->iriConverter->getIriFromItem($objectItem);

                        $ineligibilities[$category][$iri] = $categoryIneligibles;
                    }
                }

                continue;
            }

            $categoryIneligibles = $this->checkByObject($object, $programEligibilities);

            if (false === empty($categoryIneligibles)) {
                $ineligibilities[$category] = $categoryIneligibles;
            }
        }

        return $ineligibilities;
    }

    /**
     * @param object|Borrower|Project|FinancingObject $object
     * @param Collection|ProgramEligibility[]         $programEligibilities
     */
    private function checkByObject(object $object, Collection $programEligibilities): array
    {
        $ineligibles = [];

        /** @var ProgramEligibility $programEligibility */
        foreach ($programEligibilities as $programEligibility) {
            if ($this->isEligible($object, $programEligibility)) {
                continue;
            }

            $ineligibles[] = $programEligibility->getField()->getFieldAlias();
        }

        return $ineligibles;
    }

    /**
     * @param object|Borrower|Project|FinancingObject $object
     */
    private function isEligible(object $object, ProgramEligibility $programEligibility): bool
    {
        $field = $programEligibility->getField();
        $value = $this->reservationAccessor->getValue($object, $field);

        if (false === $this->isValueValid($object->getReservation(), $field, $value)) {
            return false;
        }

        if ($value instanceof Collection) {
            foreach ($value as $valueItem) {
                if (false === $this->isEligibleByObjectValue($programEligibility, $object, $valueItem)) {
                    return false;
                }
            }

            return $value->count() > 0;
        }

        return $this->isEligibleByObjectValue($programEligibility, $object, $value);
    }

    /**
     * @param object|Borrower|Project|FinancingObject $object
     * @param mixed                                   $value
     */
    private function isEligibleByObjectValue(ProgramEligibility $programEligibility, object $object, $value): bool
    {
        $programEligibilityConfiguration = $this->getConfiguration($programEligibility, $value);

        if (false === ($programEligibilityConfiguration instanceof ProgramEligibilityConfiguration)) {
            return false;
        }

        if (false === $programEligibilityConfiguration->isEligible()) {
            return false;
        }

        if (
            false === $this->eligibilityConditionChecker->checkByConfiguration(
                $object,
                $programEligibilityConfiguration
            )
        ) {
            return false;
        }

        return true;
    }

    private function isValueValid(Reservation $reservation, Field $field, $value): bool
    {
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

    private function getConfiguration(ProgramEligibility $programEligibility, $value): ?ProgramEligibilityConfiguration
    {
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
