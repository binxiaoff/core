<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Service;

use Doctrine\Common\Collections\Collection;
use LogicException;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityConfigurationRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityRepository;

class EligibilityChecker
{
    private FieldRepository $fieldRepository;
    private ProgramEligibilityRepository $programEligibilityRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;

    public function __construct(
        FieldRepository $fieldRepository,
        ProgramEligibilityRepository $programEligibilityRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository
    ) {
        $this->fieldRepository                           = $fieldRepository;
        $this->programEligibilityRepository              = $programEligibilityRepository;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
    }

    public function check(Reservation $reservation, string $category): bool
    {
        $fields = $this->fieldRepository->findBy(['category' => $category]);

        foreach ($fields as $field) {
            // ignore those not having path since they are not created yet in entities
            if (empty($field->getTargetPropertyAccessPath())) {
                continue;
            }

            $values = $this->getValues($field, $reservation);

            if (false === $this->checkEligibility($reservation, $field, $values)) {
                return false;
            }
        }

        return true;
    }

    private function checkEligibility(Reservation $reservation, Field $field, array $values): bool
    {
        $programEligibility = $this->programEligibilityRepository->findOneBy([
            'program' => $reservation->getProgram(),
            'field'   => $field,
        ]);

        if (null === $programEligibility) {
            throw new LogicException(
                sprintf(
                    'Cannot found programEligibility for program (%s) and field (%s)',
                    $reservation->getProgram()->getPublicId(),
                    $field->getPublicId()
                )
            );
        }

        if (Field::TYPE_OTHER === $field->getType() && false === $this->isOtherEligible($programEligibility, $values[0])) {
            return false;
        }

        if (Field::TYPE_BOOL === $field->getType() && false === $this->isBooleanEligible($programEligibility, $values[0])) {
            return false;
        }

        if (Field::TYPE_LIST === $field->getType() && false === $this->isListEligible($programEligibility, $values)) {
            return false;
        }

        return true;
    }

    private function isOtherEligible(ProgramEligibility $programEligibility, $value): bool
    {
        if (null === $value) {
            return false;
        }

        $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility,
        ]);

        if (null === $programEligibilityConfiguration) {
            throw new LogicException(
                sprintf(
                    'Cannot found programEligibilityConfiguration for programEligibility (%s)',
                    $programEligibility->getPublicId()
                )
            );
        }

        return $programEligibilityConfiguration->isEligible();
    }

    private function isBooleanEligible(ProgramEligibility $programEligibility, bool $value): bool
    {
        $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility,
            'value'              => (int) $value,
        ]);

        if (null === $programEligibilityConfiguration) {
            throw new LogicException(
                sprintf(
                    'Cannot found programEligibilityConfiguration for programEligibility (%s) and value (%s)',
                    $programEligibility->getPublicId(),
                    (int) $value
                )
            );
        }

        return $programEligibilityConfiguration->isEligible();
    }

    private function isListEligible(ProgramEligibility $programEligibility, array $values): bool
    {
        foreach ($values as $value) {
            if (null === $value) {
                return false;
            }

            /** @var ProgramChoiceOption $value */
            $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
                'programEligibility'  => $programEligibility,
                'programChoiceOption' => $value,
            ]);

            if (null === $programEligibilityConfiguration) {
                // an eligibility has at least a configuration ?
                // if not return true ?
                continue;
            }

            if (false === $programEligibilityConfiguration->isEligible()) {
                return false;
            }
        }

        return true;
    }

    private function getValues(Field $field, Reservation $reservation): array
    {
        $pathParts        = explode('::', $field->getTargetPropertyAccessPath());
        $entityClassParts = explode('\\', $pathParts[0]);
        $getEntity        = 'get' . end($entityClassParts);
        $entities         = $reservation->{$getEntity}();

        if (null === $entities) {
            throw new LogicException(
                sprintf(
                    'Cannot get entity/entities from targetPropertyAccessPath (%s), it should not be null.',
                    $field->getTargetPropertyAccessPath()
                )
            );
        }

        $getField    = (Field::TYPE_BOOL === $field->getType() ? 'is' : 'get') . ucfirst($pathParts[1]);
        $getSubField = (isset($pathParts[2])) ? 'get' . ucfirst($pathParts[2]) : null;
        $values      = [];

        if ($entities instanceof Collection) {
            foreach ($entities as $entity) {
                $values[] = $entity->{$getField}();
            }
        } else {
            $values[] = $entities->{$getField}();
        }

        if (null !== $getSubField) {
            foreach ($values as $key => $value) {
                $values[$key] = $value->{$getSubField}();
            }
        }

        return $values;
    }
}
