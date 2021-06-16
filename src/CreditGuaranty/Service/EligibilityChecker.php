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
    private EligibilityHelper $eligibilityHelper;
    private EligibilityConditionChecker $eligibilityConditionChecker;

    public function __construct(
        FieldRepository $fieldRepository,
        ProgramEligibilityRepository $programEligibilityRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository,
        EligibilityHelper $eligibilityHelper,
        EligibilityConditionChecker $eligibilityConditionChecker
    ) {
        $this->fieldRepository                           = $fieldRepository;
        $this->programEligibilityRepository              = $programEligibilityRepository;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
        $this->eligibilityHelper                         = $eligibilityHelper;
        $this->eligibilityConditionChecker               = $eligibilityConditionChecker;
    }

    public function checkByCategory(Reservation $reservation, string $category): bool
    {
        $fields = $this->fieldRepository->findBy(['category' => $category]);

        foreach ($fields as $field) {
            // ignore those not having path since they are not created yet in entities
            if (empty($field->getTargetPropertyAccessPath())) {
                continue;
            }

            if (false === $this->checkByField($reservation, $field)) {
                return false;
            }
        }

        return true;
    }

    private function checkByField(Reservation $reservation, Field $field): bool
    {
        $programEligibility = $this->programEligibilityRepository->findOneBy([
            'program' => $reservation->getProgram(),
            'field'   => $field,
        ]);

        if (null === $programEligibility) {
            throw new LogicException(
                sprintf(
                    'Cannot found programEligibility for program #%s and field #%s',
                    $reservation->getProgram()->getId(),
                    $field->getId()
                )
            );
        }

        $entity = $this->eligibilityHelper->getEntity($reservation, $field);

        if ($entity instanceof Collection) {
            foreach ($entity as $entityItem) {
                $value = $this->eligibilityHelper->getValue($reservation->getProgram(), $entityItem, $field);

                if (false === $this->check($reservation, $programEligibility, $value)) {
                    return false;
                }
            }

            return true;
        }

        $value = $this->eligibilityHelper->getValue($reservation->getProgram(), $entity, $field);

        return $this->check($reservation, $programEligibility, $value);
    }

    private function check(Reservation $reservation, ProgramEligibility $programEligibility, $value): bool
    {
        $field = $programEligibility->getField();

        switch ($field->getType()) {
            case Field::TYPE_OTHER:
                return $this->checkOther($reservation, $programEligibility, $value);

            case Field::TYPE_BOOL:
                return $this->checkBool($reservation, $programEligibility, (bool) $value);

            case Field::TYPE_LIST:
                return $this->checkList($reservation, $programEligibility, $value);

            default:
                // the check is done in ProgramEligibility::initialiseConfigurations
                throw new LogicException('This code should not be reached');
        }
    }

    private function checkOther(Reservation $reservation, ProgramEligibility $programEligibility, $value): bool
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
                    'Cannot found programEligibilityConfiguration for programEligibility #%s',
                    $programEligibility->getId()
                )
            );
        }

        if (false === $programEligibilityConfiguration->isEligible()) {
            return false;
        }

        return $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration);
    }

    private function checkBool(Reservation $reservation, ProgramEligibility $programEligibility, bool $value): bool
    {
        $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility,
            'value'              => (int) $value,
        ]);

        if (null === $programEligibilityConfiguration) {
            throw new LogicException(
                sprintf(
                    'Cannot found programEligibilityConfiguration for programEligibility #%s and value %s',
                    $programEligibility->getId(),
                    (int) $value
                )
            );
        }

        if (false === $programEligibilityConfiguration->isEligible()) {
            return false;
        }

        return $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration);
    }

    private function checkList(Reservation $reservation, ProgramEligibility $programEligibility, ProgramChoiceOption $value): bool
    {
        $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility,
            'programChoiceOption' => $value,
        ]);

        if (null === $programEligibilityConfiguration) {
            throw new LogicException(
                sprintf(
                    'Cannot found programEligibilityConfiguration for programEligibility #%s and programChoiceOption #%s',
                    $programEligibility->getId(),
                    $value->getId()
                )
            );
        }

        if (false === $programEligibilityConfiguration->isEligible()) {
            return false;
        }

        return $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration);
    }
}
